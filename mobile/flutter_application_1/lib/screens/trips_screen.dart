import 'package:flutter/material.dart';
import '../models/trip_assignment.dart';
import '../services/api_client.dart';
import '../theme/app_colors.dart';
import '../widgets/driver_header_menu.dart';
import 'trip_detail_screen.dart';

class TripsScreen extends StatefulWidget {
  const TripsScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<TripsScreen> createState() => _TripsScreenState();
}

class _TripsScreenState extends State<TripsScreen> {
  List<dynamic>? _cachedTrips;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (_loading) return;
    setState(() {
      _loading = _cachedTrips == null;
      _error = null;
    });
    try {
      final data = await widget.apiClient.fetchTrips();
      if (mounted) {
        setState(() {
          _cachedTrips = data;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Failed to load trips.';
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Assigned Trips'),
        actions: const [DriverHeaderMenu()],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: _buildBody(),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _load,
        icon: const Icon(Icons.refresh, color: Colors.white),
        label: const Text('Refresh', style: TextStyle(color: Colors.white)),
        backgroundColor: AppColors.darkSlateGrey,
      ),
    );
  }

  Future<void> _handleAccept(int assignmentId) async {
    try {
      await widget.apiClient.acceptTrip(assignmentId);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Trip accepted.')));
        _load();
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  Future<void> _handleDecline(int assignmentId) async {
    final reasonController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    final shouldDecline = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.report_problem, color: Colors.red),
            SizedBox(width: 10),
            Text('Decline Trip'),
          ],
        ),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Please provide a reason for declining this trip assignment.', style: TextStyle(fontSize: 13, color: Colors.grey)),
              const SizedBox(height: 16),
              TextFormField(
                controller: reasonController,
                decoration: InputDecoration(
                  labelText: 'Decline Reason',
                  alignLabelWithHint: true,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: AppColors.darkSlateGrey, width: 2),
                  ),
                ),
                validator: (v) => v == null || v.trim().isEmpty ? 'Please provide a reason' : null,
                maxLines: 3,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel', style: TextStyle(color: Colors.grey))),
          ElevatedButton(
            onPressed: () {
              if (formKey.currentState?.validate() ?? false) {
                Navigator.pop(context, true);
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.darkSlateGrey, // Company color
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Confirm Decline'),
          ),
        ],
      ),
    );

    if (shouldDecline == true) {
      try {
        await widget.apiClient.declineTrip(assignmentId, reasonController.text.trim());
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Trip declined.')));
          _load();
        }
      } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  Widget _buildBody() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    
    if (_error != null && _cachedTrips == null) {
      return Center(child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.error_outline, size: 48, color: Colors.redAccent),
          const SizedBox(height: 12),
          Text(_error!, textAlign: TextAlign.center),
          TextButton(onPressed: _load, child: const Text('Try Again')),
        ],
      ));
    }

    final items = _cachedTrips ?? [];
    if (items.isEmpty) {
      return const Center(child: Text('No trips assigned yet.'));
    }

    final assignments = items.map((item) => TripAssignment.fromJson(item as Map<String, dynamic>)).toList();

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: assignments.length,
      itemBuilder: (context, index) => _buildTripCard(assignments[index]),
    );
  }

  Widget _buildTripCard(TripAssignment assignment) {
    final trip = assignment.trip;
    final status = assignment.status.toLowerCase();
    final origin = trip['route_from'] ?? '-';
    final destination = trip['route_to'] ?? '-';
    
    Color statusColor = status == 'pending' ? Colors.orange : (status == 'accepted' || status == 'in_transit' ? Colors.green : Colors.grey);

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(color: statusColor.withOpacity(0.1), borderRadius: const BorderRadius.vertical(top: Radius.circular(16))),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(status.toUpperCase(), style: TextStyle(color: statusColor, fontWeight: FontWeight.w800, fontSize: 11)),
                Text('#${trip['id'] ?? '??'}', style: const TextStyle(color: Colors.grey, fontSize: 11, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  children: [
                    const Icon(Icons.location_on, color: Colors.blue, size: 18),
                    const SizedBox(width: 12),
                    Expanded(child: Text(origin, style: const TextStyle(fontWeight: FontWeight.bold))),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    const Icon(Icons.flag, color: Colors.redAccent, size: 18),
                    const SizedBox(width: 12),
                    Expanded(child: Text(destination, style: const TextStyle(fontWeight: FontWeight.bold))),
                  ],
                ),
                if (status == 'pending') ...[
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      Expanded(child: ElevatedButton(
                        onPressed: () => _handleAccept(assignment.assignmentId),
                        style: ElevatedButton.styleFrom(backgroundColor: AppColors.darkSlateGrey, foregroundColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
                        child: const Text('Accept Trip', style: TextStyle(fontWeight: FontWeight.bold)),
                      )),
                      const SizedBox(width: 12),
                      Expanded(child: OutlinedButton(
                        onPressed: () => _handleDecline(assignment.assignmentId),
                        style: OutlinedButton.styleFrom(foregroundColor: Colors.red, side: const BorderSide(color: Colors.red), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
                        child: const Text('Decline'),
                      )),
                    ],
                  ),
                ] else
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton.icon(
                      onPressed: () async {
                        final refresh = await Navigator.push<bool>(context, MaterialPageRoute(builder: (_) => TripDetailScreen(assignment: assignment, apiClient: widget.apiClient)));
                        if (refresh == true) _load();
                      },
                      icon: const Icon(Icons.arrow_forward),
                      label: const Text('Details'),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _NoTripsAnimation extends StatelessWidget {
  const _NoTripsAnimation();
  @override
  Widget build(BuildContext context) => const Icon(Icons.local_shipping_outlined, size: 64, color: Colors.grey);
}
