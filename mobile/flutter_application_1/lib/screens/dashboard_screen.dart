import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../services/api_client.dart';
import '../services/driver_profile_store.dart';
import '../theme/app_colors.dart';
import '../widgets/driver_header_menu.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final DriverProfileStore _profileStore = DriverProfileStore();
  String _driverName = 'Driver';
  String _tripRoute = 'Ready for assignment';
  String _origin = '-';
  String _destination = '-';
  String _locationLabel = 'Locating...';
  String _earnings = '0';
  String _currency = 'KES';
  int _alertCount = 0;
  double _fuelProgress = 0.65;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      // 1. Fetch Profile for Name
      final profile = await _profileStore.read();
      if (profile != null) {
        _driverName = profile['driver_name']?.split(' ').first ?? 'Driver';
      }

      // 2. Fetch Trips & Active Mission
      final trips = await widget.apiClient.fetchTrips();
      final activeTrip = trips.firstWhere(
        (t) => t['status'] == 'in_transit' || t['status'] == 'active' || t['status'] == 'accepted',
        orElse: () => null,
      );
      
      final tripData = activeTrip?['trip'] as Map<String, dynamic>?;
      if (tripData != null) {
        _origin = tripData['route_from'] ?? '-';
        _destination = tripData['route_to'] ?? '-';
        _tripRoute = '$_origin → $_destination';
      } else {
        _tripRoute = 'Ready for next mission';
      }

      // 3. Fetch Financials
      final summary = await widget.apiClient.fetchPaymentSummary();
      final walletBalance = summary['wallet_balance'];
      final money = walletBalance is num ? walletBalance.toDouble() : double.tryParse('$walletBalance') ?? 0;
      _earnings = money.toStringAsFixed(0);
      _currency = summary['currency'] as String? ?? 'KES';


      // 4. Fetch Alerts
      final alerts = await widget.apiClient.fetchAlerts();
      _alertCount = (alerts['alerts'] as List? ?? []).length;

      // 5. Update Location
      final pos = await _safePosition();
      if (pos != null) {
        _locationLabel = await _resolveLocationName(pos);
      } else {
        _locationLabel = 'Location access required';
      }

      if (mounted) setState(() => _loading = false);
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<Position?> _safePosition() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled()) return null;
      var p = await Geolocator.checkPermission();
      if (p == LocationPermission.denied) p = await Geolocator.requestPermission();
      if (p == LocationPermission.always || p == LocationPermission.whileInUse) {
        return await Geolocator.getCurrentPosition();
      }
      return null;
    } catch (_) { return null; }
  }

  Future<String> _resolveLocationName(Position pos) async {
    try {
      final res = await http.get(Uri.https('nominatim.openstreetmap.org', '/reverse', {
        'format': 'jsonv2', 'lat': '${pos.latitude}', 'lon': '${pos.longitude}', 'zoom': '16'
      }));
      if (res.statusCode == 200) {
        final address = jsonDecode(res.body)['address'];
        return address['suburb'] ?? address['city'] ?? address['road'] ?? 'Current Location';
      }
    } catch (_) {}
    return '${pos.latitude.toStringAsFixed(4)}, ${pos.longitude.toStringAsFixed(4)}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: _loading 
        ? const Center(child: CircularProgressIndicator(color: AppColors.darkSlateGrey))
        : RefreshIndicator(
            onRefresh: _load,
            color: AppColors.darkSlateGrey,
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                _buildHeader(),
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 100),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(height: 24),
                        _sectionTitle('Active Mission'),
                        const SizedBox(height: 12),
                        _missionHeroCard(),
                        const SizedBox(height: 28),
                        _sectionTitle('Quick Status'),
                        const SizedBox(height: 12),
                        _metricsGrid(),
                        const SizedBox(height: 28),
                        _sectionTitle('Vehicle Vitals'),
                        const SizedBox(height: 12),
                        _vitalsCard(),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
    );
  }

  Widget _buildHeader() {
    return SliverAppBar(
      expandedHeight: 140,
      pinned: true,
      elevation: 0,
      backgroundColor: AppColors.darkSlateGrey,
      actions: const [DriverHeaderMenu()],
      flexibleSpace: FlexibleSpaceBar(
        titlePadding: const EdgeInsets.only(left: 20, bottom: 16),
        title: Text(
          'Hello, $_driverName',
          style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: Colors.white),
        ),
        background: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [AppColors.darkSlateGrey, Color(0xFF1E3333)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
      ),
    );
  }

  Widget _sectionTitle(String title) {
    return Text(
      title.toUpperCase(),
      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF64748B), letterSpacing: 1.2),
    );
  }

  Widget _missionHeroCard() {
    final hasTrip = _origin != '-';
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(color: const Color(0xFF0F172A).withOpacity(0.04), blurRadius: 20, offset: const Offset(0, 8)),
        ],
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _locationRow(Icons.circle, Colors.blue, 'FROM', _origin),
                      const Padding(
                        padding: EdgeInsets.only(left: 7),
                        child: SizedBox(height: 20, child: VerticalDivider(width: 1, thickness: 1, color: Color(0xFFE2E8F0))),
                      ),
                      _locationRow(Icons.location_on, Colors.redAccent, 'TO', _destination),
                    ],
                  ),
                ),
                if (hasTrip)
                  Container(
                    height: 80, width: 80,
                    decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(16)),
                    child: const Icon(Icons.map_outlined, color: AppColors.darkSlateGrey, size: 32),
                  ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
            decoration: BoxDecoration(
              color: hasTrip ? const Color(0xFFF8FAFC) : Colors.amber.shade50,
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(24)),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline, size: 14, color: hasTrip ? Colors.grey : Colors.amber.shade700),
                const SizedBox(width: 8),
                Text(
                  hasTrip ? 'Estimated travel time: 4h 20m' : 'Standby for assignments',
                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: hasTrip ? Colors.grey : Colors.amber.shade800),
                ),
                const Spacer(),
                const Icon(Icons.arrow_forward, size: 14, color: Colors.grey),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _locationRow(IconData icon, Color color, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 12),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.grey)),
            Text(value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF1E293B))),
          ],
        ),
      ],
    );
  }

  Widget _metricsGrid() {
    return Row(
      children: [
        Expanded(child: _metricCard('Wallet Balance', '$_currency $_earnings', Icons.account_balance_wallet, Colors.green)),
        const SizedBox(width: 16),
        Expanded(child: _metricCard('Active Alerts', '$_alertCount', Icons.bolt_rounded, Colors.orange)),
      ],
    );
  }


  Widget _metricCard(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(color: const Color(0xFF0F172A).withOpacity(0.03), blurRadius: 15, offset: const Offset(0, 5)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(10)),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(height: 16),
          Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Color(0xFF0F172A))),
          Text(label, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey)),
        ],
      ),
    );
  }

  Widget _vitalsCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(color: const Color(0xFF0F172A).withOpacity(0.03), blurRadius: 15, offset: const Offset(0, 5)),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              const CircleAvatar(backgroundColor: Color(0xFFF1F5F9), child: Icon(Icons.pin_drop, color: AppColors.darkSlateGrey, size: 18)),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('CURRENT LOCATION', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.grey)),
                    Text(_locationLabel, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF1E293B)), overflow: TextOverflow.ellipsis),
                  ],
                ),
              ),
            ],
          ),
          const Padding(padding: EdgeInsets.symmetric(vertical: 16), child: Divider(height: 1, color: Color(0xFFF1F5F9))),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('FUEL LEVEL', style: TextStyle(fontSize: 9, fontWeight: FontWeight.bold, color: Colors.grey)),
              Text('${(_fuelProgress * 100).toInt()}%', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900, color: AppColors.darkSlateGrey)),
            ],
          ),
          const SizedBox(height: 10),
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: _fuelProgress,
              minHeight: 10,
              backgroundColor: const Color(0xFFF1F5F9),
              color: _fuelProgress < 0.2 ? Colors.red : AppColors.darkSlateGrey,
            ),
          ),
        ],
      ),
    );
  }
}
