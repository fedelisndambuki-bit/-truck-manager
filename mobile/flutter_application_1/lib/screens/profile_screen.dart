import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../services/driver_profile_store.dart';
import '../theme/app_colors.dart';
import '../widgets/driver_header_menu.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final DriverProfileStore _profileStore = DriverProfileStore();
  Map<String, dynamic>? _profile;
  List<Map<String, dynamic>> _assignedTrucks = const [];
  bool _isLoggingOut = false;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    final localProfile = await _profileStore.read();
    if (mounted) {
      setState(() {
        _profile = localProfile;
        _assignedTrucks = _extractAssignedTrucks(localProfile);
      });
    }

    try {
      final remote = await widget.apiClient.fetchDriverProfile();
      final remoteProfile = remote['profile'];
      if (!mounted) {
        return;
      }

      if (remoteProfile is Map) {
        setState(() {
          _profile = Map<String, dynamic>.from(remoteProfile);
          _assignedTrucks = _extractAssignedTrucks(_profile);
        });
      }
    } catch (_) {
      // Keep local profile if refresh fails.
    }
  }

  List<Map<String, dynamic>> _extractAssignedTrucks(
    Map<String, dynamic>? profile,
  ) {
    if (profile == null) {
      return const [];
    }

    final raw = profile['assigned_trucks'];
    if (raw is! List) {
      return const [];
    }

    return raw
        .whereType<Map>()
        .map((entry) => Map<String, dynamic>.from(entry))
        .toList();
  }

  Widget _buildDetailRow(IconData icon, String label, String? value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppColors.darkSlateGrey),
          const SizedBox(width: 8),
          Text('$label: ', style: const TextStyle(fontWeight: FontWeight.bold)),
          Expanded(child: Text(value ?? 'N/A')),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoggingOut) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(
            color: AppColors.darkSlateGrey,
          ),
        ),
      );
    }

    final name = _profile?['driver_name'] as String? ?? 'Driver';
    final imageUrl = _profile?['driver_profile_url'] as String?;
    final company =
        _profile?['company_name'] as String? ?? 'Assigned driver account';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Profile'),
        actions: const [DriverHeaderMenu()],
      ),
      body: RefreshIndicator(
        onRefresh: _loadProfile,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Card(
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: Colors.white,
                    backgroundImage: (imageUrl != null && imageUrl.isNotEmpty)
                        ? NetworkImage(imageUrl)
                        : null,
                    child: (imageUrl == null || imageUrl.isEmpty)
                        ? const Icon(Icons.person, color: AppColors.darkSlateGrey)
                        : null,
                  ),
                  title: Text(name),
                  subtitle: Text(company),
                ),
              ),
              const SizedBox(height: 20),
              
              // 1. Assigned Truck First
              Text(
                'Assigned Truck',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              if (_assignedTrucks.isEmpty)
                const Card(
                  child: ListTile(
                    leading: Icon(Icons.local_shipping_outlined),
                    title: Text('No truck assigned yet'),
                    subtitle: Text('Your dispatcher will assign one shortly.'),
                  ),
                )
              else
                ..._assignedTrucks.map((truck) => _buildTruckCard(truck)),
                
              const SizedBox(height: 24),

              // 2. Other Driver Information
              Text(
                'Personal Information',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              if (_profile?['driver_details'] != null)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        _buildDetailRow(Icons.email, 'Email', _profile?['driver_details']['email']),
                        const Divider(),
                        _buildDetailRow(Icons.phone, 'Phone', _profile?['driver_details']['phone']),
                        const Divider(),
                        _buildDetailRow(Icons.badge, 'ID Number', _profile?['driver_details']['national_id']),
                        const Divider(),
                        _buildDetailRow(Icons.credit_card, 'License', _profile?['driver_details']['license_number']),
                      ],
                    ),
                  ),
                )
              else
                const Card(
                  child: ListTile(
                    title: Text('Profile details unavailable'),
                  ),
                ),

              const SizedBox(height: 32),

              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => _handleSignOut(context),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.darkSlateGrey,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  child: const Text('Sign Out'),
                ),
              ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTruckCard(Map<String, dynamic> truck) {
    final plate = '${truck['plate_number'] ?? '-'}';
    final status = '${truck['status'] ?? '-'}';
    final brand = '${truck['brand'] ?? '-'}';
    final model = '${truck['model'] ?? '-'}';
    final mileage = '${truck['current_mileage'] ?? 0}';
    final fuel = '${truck['fuel_type'] ?? '-'}';
    final lastService = '${truck['last_service_date'] ?? 'N/A'}';
    final truckImageUrl = truck['truck_image_url'] as String?;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          if (truckImageUrl != null && truckImageUrl.isNotEmpty)
            Container(
              color: const Color(0xFFF1F5F9),
              height: 120, // Slightly shorter for a cleaner look
              width: double.infinity,
              padding: const EdgeInsets.all(8), // Padding so it's "seen it all"
              child: Image.network(
                truckImageUrl,
                fit: BoxFit.contain, // Ensures the entire image is visible
              ),
            )
          else
            Container(
              height: 80, // Reduced from 100
              color: Colors.grey[200],
              child: const Icon(Icons.local_shipping, size: 32, color: Colors.grey),
            ),
          ListTile(
            dense: true, // More compact
            title: Text('$brand $model', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
            subtitle: Text(plate, style: const TextStyle(fontSize: 11)),
            trailing: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: _getStatusColor(status),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                status.toUpperCase(),
                style: const TextStyle(fontSize: 9, color: Colors.white, fontWeight: FontWeight.bold),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12), // Reduced bottom padding
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _miniStat(Icons.route, '$mileage km'),
                _miniStat(Icons.local_gas_station, fuel),
                _miniStat(Icons.calendar_today, 'Svc: $lastService'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _miniStat(IconData icon, String label) {
    return Row(
      children: [
        Icon(icon, size: 14, color: AppColors.textMuted),
        const SizedBox(width: 4),
        Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
      ],
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'active':
      case 'available': return Colors.green;
      case 'maintenance': return Colors.orange;
      default: return Colors.grey;
    }
  }

  Future<void> _handleSignOut(BuildContext context) async {
    final shouldSignOut = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Sign Out'),
        content: const Text('Are you sure you want to sign out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(dialogContext).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.darkSlateGrey, foregroundColor: Colors.white),
            child: const Text('Sign Out'),
          ),
        ],
      ),
    );

    if (shouldSignOut != true) return;

    setState(() => _isLoggingOut = true);
    try {
      await widget.apiClient.logout();
    } catch (_) {}

    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => LoginScreen(apiClient: widget.apiClient)),
      (_) => false,
    );
  }
}
