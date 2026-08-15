import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../services/connectivity_service.dart';
import 'live_trips_screen.dart';
import 'trips_screen.dart';
import 'payments_screen.dart';
import 'chat_screen.dart';
import 'alerts_screen.dart';
import 'profile_screen.dart';
import 'dashboard_screen.dart';
import 'safety_screen.dart';
import 'tyre_management_screen.dart';
import '../services/upload_queue_service.dart';
import '../widgets/connection_status_widget.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _currentIndex = 0;
  final UploadQueueService _queueService = UploadQueueService();
  Timer? _unreadTimer;
  int _unreadChatCount = 0;
  late final ConnectivityService _connectivityService;
  bool _wasOffline = false;

  @override
  void initState() {
    super.initState();
    _connectivityService = ConnectivityService();
    _connectivityService.addListener(_onConnectivityChanged);
    _wasOffline = !_connectivityService.isConnected;

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final uploaded = await _queueService.retryQueuedUploads(widget.apiClient);
      if (!mounted || uploaded == 0) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Synced $uploaded queued upload(s).')),
      );
    });
    _refreshUnreadCount();
    _unreadTimer = Timer.periodic(const Duration(seconds: 6), (_) => _refreshUnreadCount());
    widget.apiClient.fetchDriverProfile().catchError((_) {
      // Keep using cached profile if refresh fails.
      return <String, dynamic>{};
    });
  }

  void _onConnectivityChanged(bool isConnected) {
    if (isConnected && _wasOffline) {
      // Connection restored, retry queued uploads
      _wasOffline = false;
      _retryOnConnectionRestored();
    } else if (!isConnected) {
      _wasOffline = true;
    }
  }

  Future<void> _retryOnConnectionRestored() async {
    if (!mounted) return;

    // Retry queued uploads
    try {
      final uploaded = await _queueService.retryQueuedUploads(widget.apiClient);
      if (mounted && uploaded > 0) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Auto-synced $uploaded upload(s) after reconnection.'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (_) {
      // Silently fail
    }

    // Refresh profile
    try {
      await widget.apiClient.fetchDriverProfile();
    } catch (_) {
      // Keep using cached profile if refresh fails.
    }

    // Refresh unread count
    _refreshUnreadCount();
  }

  @override
  void dispose() {
    _unreadTimer?.cancel();
    _connectivityService.removeListener(_onConnectivityChanged);
    super.dispose();
  }

  Future<void> _refreshUnreadCount() async {
    try {
      final count = await widget.apiClient.fetchChatUnreadCount();
      if (mounted) {
        setState(() => _unreadChatCount = count);
      }
    } catch (_) {
      // Ignore polling failures.
    }
  }

  Widget _chatIconWithBadge() {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        const Icon(Icons.chat_bubble_outline),
        if (_unreadChatCount > 0)
          Positioned(
            right: -8,
            top: -6,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
              decoration: BoxDecoration(
                color: Colors.orange,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                _unreadChatCount > 99 ? '99+' : '$_unreadChatCount',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      DashboardScreen(apiClient: widget.apiClient),
      LiveTripsScreen(apiClient: widget.apiClient),
      TripsScreen(apiClient: widget.apiClient),
      TyreManagementScreen(apiClient: widget.apiClient),
      PaymentsScreen(apiClient: widget.apiClient),
      ChatScreen(apiClient: widget.apiClient),
      AlertsScreen(apiClient: widget.apiClient),
      SafetyScreen(apiClient: widget.apiClient),
      ProfileScreen(apiClient: widget.apiClient),
    ];

    return ConnectionStatusWidget(
      child: Scaffold(
        body: IndexedStack(
          index: _currentIndex,
          children: pages,
        ),
        bottomNavigationBar: NavigationBar(
          selectedIndex: _currentIndex,
          labelBehavior: NavigationDestinationLabelBehavior.onlyShowSelected,
          onDestinationSelected: (value) async {
            setState(() => _currentIndex = value);
            if (value == 5) {
              await _refreshUnreadCount();
            }
          },
          destinations: [
            const NavigationDestination(icon: Icon(Icons.dashboard_customize), label: 'Dashboard'),
            const NavigationDestination(icon: Icon(Icons.map_rounded), label: 'Live'),
            const NavigationDestination(icon: Icon(Icons.local_shipping), label: 'Trips'),
            const NavigationDestination(icon: Icon(Icons.settings_input_component), label: 'Tyres'),
            const NavigationDestination(icon: Icon(Icons.payments), label: 'Pay'),
            NavigationDestination(icon: _chatIconWithBadge(), label: 'Chat'),
            const NavigationDestination(icon: Icon(Icons.notifications), label: 'Alerts'),
            const NavigationDestination(icon: Icon(Icons.health_and_safety), label: 'Safety'),
            const NavigationDestination(icon: Icon(Icons.person), label: 'Profile'),
          ],
        ),
      ),
    );
  }
}
