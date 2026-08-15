import 'package:flutter/material.dart';
import 'services/api_client.dart';
import 'services/token_store.dart';
import 'services/theme_mode_store.dart';
import 'services/connectivity_service.dart';
import 'theme/app_theme.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';
import 'screens/profile_screen.dart';

class DriverApp extends StatefulWidget {
  const DriverApp({super.key});

  @override
  State<DriverApp> createState() => _DriverAppState();
}

class _DriverAppState extends State<DriverApp> {
  final TokenStore _tokenStore = TokenStore();
  final ConnectivityService _connectivityService = ConnectivityService();
  bool _showIntro = true;
  late GlobalKey<NavigatorState> _navigatorKey;

  @override
  void initState() {
    super.initState();
    _navigatorKey = GlobalKey<NavigatorState>();
    _initializeServices();
  }

  void _initializeServices() async {
    // Initialize connectivity monitoring
    await _connectivityService.initialize();

    // Listen for connectivity changes
    _connectivityService.addListener(_onConnectivityChanged);

    // Show intro screen for 1 second
    Future<void>.delayed(const Duration(seconds: 1), () {
      if (mounted) {
        setState(() => _showIntro = false);
      }
    });
  }

  void _onConnectivityChanged(bool isConnected) {
    if (isConnected) {
      // Show a snackbar when connection is restored
      if (mounted) {
        ScaffoldMessenger.of(_navigatorKey.currentContext ?? context)
            .showSnackBar(
          const SnackBar(
            content: Text('Internet connection restored'),
            duration: Duration(seconds: 2),
            backgroundColor: Colors.green,
          ),
        );
      }
    } else {
      // Show a snackbar when connection is lost
      if (mounted) {
        ScaffoldMessenger.of(_navigatorKey.currentContext ?? context)
            .showSnackBar(
          const SnackBar(
            content: Text('Internet connection lost'),
            duration: Duration(seconds: 2),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _connectivityService.removeListener(_onConnectivityChanged);
    _connectivityService.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_showIntro) {
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        home: Scaffold(
          backgroundColor: const Color(0xFF2F4F4F),
          body: Center(
            child: Text(
              'ShiftHub Driver',
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w900,
                fontSize: 56,
                letterSpacing: 0.4,
              ),
            ),
          ),
        ),
      );
    }

    return ValueListenableBuilder(
      valueListenable: ThemeModeStore.mode,
      builder: (context, mode, _) {
        return MaterialApp(
          title: 'ShiftHub Driver',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.light(),
          darkTheme: AppTheme.dark(),
          themeMode: mode,
          navigatorKey: _navigatorKey,
          routes: {
            '/profile': (_) => ProfileScreen(
                  apiClient: ApiClient(tokenStore: _tokenStore),
                ),
          },
          home: FutureBuilder<String?>(
            future: _tokenStore.read(),
            builder: (context, snapshot) {
              final token = snapshot.data;
              if (token == null || token.isEmpty) {
                return LoginScreen(
                  apiClient: ApiClient(tokenStore: _tokenStore),
                );
              }
              return HomeScreen(
                apiClient: ApiClient(tokenStore: _tokenStore),
              );
            },
          ),
        );
      },
    );
  }
}
