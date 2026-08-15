import 'dart:async';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:geolocator/geolocator.dart';
import 'package:latlong2/latlong.dart';
import '../services/api_client.dart';
import '../services/upload_queue_service.dart';
import '../theme/app_colors.dart';
import 'chat_screen.dart';
import '../widgets/driver_header_menu.dart';

class LiveTripsScreen extends StatefulWidget {
  const LiveTripsScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<LiveTripsScreen> createState() => _LiveTripsScreenState();
}

class _LiveTripsScreenState extends State<LiveTripsScreen> {
  final MapController _mapController = MapController();

  StreamSubscription<Position>? _positionSubscription;
  LatLng? _currentPoint;
  final List<LatLng> _trail = [];
  DateTime? _lastLocationAt;
  DateTime? _lastSyncAt;

  bool _tracking = false;
  bool _permissionDenied = false;
  double _speedKmh = 0;
  double _heading = 0;
  String _status = 'Offline';
  Timer? _unreadTimer;
  int _unreadChatCount = 0;
  final UploadQueueService _queueService = UploadQueueService();
  
  Map<String, dynamic>? _activeTrip;

  @override
  void initState() {
    super.initState();
    _startTracking();
    _fetchActiveTrip();
    _refreshUnreadCount();
    _unreadTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      _refreshUnreadCount();
      if (_activeTrip == null) _fetchActiveTrip();
    });
  }

  @override
  void dispose() {
    _positionSubscription?.cancel();
    _unreadTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchActiveTrip() async {
    try {
      final List<dynamic> data = await widget.apiClient.fetchTrips();
      final active = data.firstWhere(
        (t) => t['status'] == 'accepted' || t['status'] == 'in_transit',
        orElse: () => null,
      );
      if (mounted) {
        setState(() => _activeTrip = active?['trip']);
      }
    } catch (_) {}
  }

  Future<void> _refreshUnreadCount() async {
    try {
      final count = await widget.apiClient.fetchChatUnreadCount();
      if (mounted) {
        setState(() => _unreadChatCount = count);
      }
    } catch (_) {}
  }

  Future<void> _startTracking() async {
    final ready = await _ensureLocationReady();
    if (!ready) {
      setState(() {
        _tracking = false;
        _permissionDenied = true;
      });
      return;
    }

    await _positionSubscription?.cancel();

    final locationSettings = const LocationSettings(
      accuracy: LocationAccuracy.bestForNavigation,
      distanceFilter: 5,
    );

    _positionSubscription = Geolocator.getPositionStream(
      locationSettings: locationSettings,
    ).listen(_onPosition);

    setState(() {
      _tracking = true;
      _permissionDenied = false;
      _status = 'Online';
    });
  }

  Future<void> _stopTracking() async {
    await _positionSubscription?.cancel();
    _positionSubscription = null;
    setState(() {
      _tracking = false;
      _speedKmh = 0;
      _status = 'Offline';
    });
  }

  Future<bool> _ensureLocationReady() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) return false;

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    return !(permission == LocationPermission.denied || permission == LocationPermission.deniedForever);
  }

  Future<void> _onPosition(Position position) async {
    final point = LatLng(position.latitude, position.longitude);
    final speedKmh = (position.speed * 3.6).clamp(0, 300).toDouble();
    final status = speedKmh >= 5 ? 'Driving' : 'Idle';

    if (_trail.isEmpty || _distanceMeters(_trail.last, point) > 2) {
      _trail.add(point);
      if (_trail.length > 150) _trail.removeAt(0);
    }

    _currentPoint = point;
    _speedKmh = speedKmh;
    _heading = position.heading;
    _status = status;
    _lastLocationAt = DateTime.now();

    if (mounted) {
      setState(() {});
      _mapController.move(point, 16.5);
    }

    final now = DateTime.now();
    if (_lastSyncAt == null || now.difference(_lastSyncAt!).inSeconds >= 10) {
      _lastSyncAt = now;
      try {
        await widget.apiClient.sendLocation(
          latitude: position.latitude,
          longitude: position.longitude,
          speed: speedKmh,
        );
      } catch (_) {
        await _queueService.enqueueLocation(
          latitude: position.latitude,
          longitude: position.longitude,
          speed: speedKmh,
        );
      }
    }
  }

  double _distanceMeters(LatLng a, LatLng b) {
    return Geolocator.distanceBetween(a.latitude, a.longitude, b.latitude, b.longitude);
  }

  @override
  Widget build(BuildContext context) {
    final mapCenter = _currentPoint ?? const LatLng(-1.286389, 36.817223);
    
    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: Container(
          margin: const EdgeInsets.all(8),
          decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
          child: const BackButton(color: Colors.black87),
        ),
        actions: [
          Container(
            margin: const EdgeInsets.all(8),
            decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
            child: IconButton(
              icon: Icon(_tracking ? Icons.pause_rounded : Icons.play_arrow_rounded, color: AppColors.primary),
              onPressed: _tracking ? _stopTracking : _startTracking,
            ),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Stack(
        children: [
          // Modern Map
          FlutterMap(
            mapController: _mapController,
            options: MapOptions(
              initialCenter: mapCenter,
              initialZoom: 16,
              interactionOptions: const InteractionOptions(flags: InteractiveFlag.all),
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                subdomains: const ['a', 'b', 'c'],
                userAgentPackageName: 'com.example.flutter_application_1',
                // Detailed tiles often look better with retina mode or higher zoom logic
                retinaMode: true,
              ),
              if (_trail.length > 1)
                PolylineLayer(
                  polylines: [
                    Polyline(
                      points: _trail,
                      strokeWidth: 5,
                      color: AppColors.primary.withOpacity(0.5),
                    ),
                  ],
                ),
              if (_currentPoint != null)
                MarkerLayer(
                  markers: [
                    Marker(
                      point: _currentPoint!,
                      width: 80,
                      height: 80,
                      child: _PulseMarker(heading: _heading),
                    ),
                  ],
                ),
            ],
          ),

          // Top Floating Search/Status
          Positioned(
            top: MediaQuery.of(context).padding.top + 60,
            left: 20,
            right: 20,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, 4))],
              ),
              child: Row(
                children: [
                  Container(
                    width: 10, height: 10,
                    decoration: BoxDecoration(
                      color: _tracking ? Colors.green : Colors.grey,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text(
                              _tracking ? 'GPS Status: $_status' : 'System: Offline',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                            ),
                            const SizedBox(width: 6),
                            if (_tracking && _lastSyncAt == null)
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                                decoration: BoxDecoration(color: Colors.orange, borderRadius: BorderRadius.circular(4)),
                                child: const Text('OFFLINE', style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold)),
                              ),
                          ],
                        ),
                        if (_tracking)
                          const Text(
                            'Logging location (Offline Support Active)',
                            style: TextStyle(fontSize: 10, color: Colors.grey),
                          ),
                      ],
                    ),
                  ),
                  Text(
                    '${_speedKmh.toStringAsFixed(0)} km/h',
                    style: const TextStyle(fontWeight: FontWeight.w900, color: AppColors.primary, fontSize: 16),
                  ),
                ],
              ),
            ),
          ),

          // Bottom Sheet Style Info
          Positioned(
            left: 0, right: 0, bottom: 0,
            child: Container(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 20, offset: const Offset(0, -5))],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (_activeTrip != null) ...[
                    Row(
                      children: [
                        const Icon(Icons.route_rounded, color: AppColors.primary),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('ACTIVE MISSION', style: TextStyle(fontSize: 10, fontWeight: FontWeight.w900, letterSpacing: 1, color: Colors.grey)),
                              Text(
                                '${_activeTrip!['origin']} → ${_activeTrip!['destination']}',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                maxLines: 1, overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(color: Colors.blue.shade50, borderRadius: BorderRadius.circular(20)),
                          child: Text(_activeTrip!['status'].toString().toUpperCase(), style: TextStyle(color: Colors.blue.shade700, fontSize: 10, fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                    const Divider(height: 32),
                  ],
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceAround,
                    children: [
                      _actionBtn(Icons.chat_bubble_rounded, 'Chat', _unreadChatCount, () {
                        Navigator.push(context, MaterialPageRoute(builder: (_) => ChatScreen(apiClient: widget.apiClient)));
                      }),
                      _actionBtn(Icons.my_location_rounded, 'Recenter', 0, () {
                        if (_currentPoint != null) _mapController.move(_currentPoint!, 16.5);
                      }),
                      _actionBtn(Icons.warning_amber_rounded, 'SOS', 0, () {
                        _showSOSConfirmation();
                      }),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showSOSConfirmation() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.emergency_share_rounded, size: 64, color: Colors.red),
            const SizedBox(height: 16),
            const Text(
              'Confirm SOS Alert',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              'This will alert the control room of your current location and active mission for immediate assistance.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 32),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancel'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.pop(context);
                      _triggerSOS();
                    },
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
                    child: const Text('SEND SOS', style: TextStyle(fontWeight: FontWeight.w900)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _triggerSOS() async {
    try {
      await widget.apiClient.triggerSOS(
        tripId: _activeTrip?['id'],
        latitude: _currentPoint?.latitude,
        longitude: _currentPoint?.longitude,
        message: 'Emergency SOS triggered from mobile map.',
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('SOS alert sent successfully.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to send SOS: $e')),
        );
      }
    }
  }

  Widget _actionBtn(IconData icon, String label, int badge, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      child: Column(
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: Colors.grey.shade100, shape: BoxShape.circle),
                child: Icon(icon, color: Colors.black87),
              ),
              if (badge > 0)
                Positioned(
                  right: -2, top: -2,
                  child: Container(
                    padding: const EdgeInsets.all(4),
                    decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                    constraints: const BoxConstraints(minWidth: 18, minHeight: 18),
                    child: Text('$badge', style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold), textAlign: TextAlign.center),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 8),
          Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Colors.black54)),
        ],
      ),
    );
  }
}

class _PulseMarker extends StatefulWidget {
  const _PulseMarker({required this.heading});
  final double heading;

  @override
  State<_PulseMarker> createState() => _PulseMarkerState();
}

class _PulseMarkerState extends State<_PulseMarker> with TickerProviderStateMixin {
  late AnimationController _pulseController;
  
  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(vsync: this, duration: const Duration(seconds: 2))..repeat();
  }

  @override
  void dispose() {
    _pulseController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _pulseController,
      builder: (context, child) {
        return Stack(
          alignment: Alignment.center,
          children: [
            // Outer Glow Pulse
            Container(
              width: 70 * _pulseController.value,
              height: 70 * _pulseController.value,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.primary.withOpacity(0.3 * (1 - _pulseController.value)),
              ),
            ),
            // Smooth Rotation Wrapper
            TweenAnimationBuilder<double>(
              tween: Tween<double>(begin: 0, end: widget.heading),
              duration: const Duration(milliseconds: 800),
              curve: Curves.easeOutCubic,
              builder: (context, heading, child) {
                return Transform.rotate(
                  angle: heading * math.pi / 180,
                  child: _buildTruckBody(),
                );
              },
            ),
          ],
        );
      },
    );
  }

  Widget _buildTruckBody() {
    return Container(
      width: 42,
      height: 22,
      decoration: BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.circular(4),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.3),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Stack(
        children: [
          // Cabin (Front)
          Positioned(
            right: 0, top: 0, bottom: 0,
            child: Container(
              width: 14,
              decoration: BoxDecoration(
                color: Colors.black.withOpacity(0.15),
                borderRadius: const BorderRadius.horizontal(right: Radius.circular(4)),
              ),
            ),
          ),
          // Windshield
          Positioned(
            right: 4, top: 4, bottom: 4,
            child: Container(
              width: 4,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.4),
                borderRadius: BorderRadius.circular(1),
              ),
            ),
          ),
          // Headlights
          Positioned(
            right: -1, top: 2,
            child: _light(Colors.white.withOpacity(0.8)),
          ),
          Positioned(
            right: -1, bottom: 2,
            child: _light(Colors.white.withOpacity(0.8)),
          ),
          // Tail lights
          Positioned(
            left: 1, top: 2,
            child: _light(Colors.red.withOpacity(0.6)),
          ),
          Positioned(
            left: 1, bottom: 2,
            child: _light(Colors.red.withOpacity(0.6)),
          ),
        ],
      ),
    );
  }

  Widget _light(Color color) {
    return Container(
      width: 3, height: 3,
      decoration: BoxDecoration(color: color, shape: BoxShape.circle),
    );
  }
}

