import 'package:connectivity_plus/connectivity_plus.dart';

class ConnectivityService {
  static final ConnectivityService _instance = ConnectivityService._internal();

  factory ConnectivityService() {
    return _instance;
  }

  ConnectivityService._internal();

  final Connectivity _connectivity = Connectivity();
  bool _isConnected = true;
  final List<void Function(bool)> _listeners = [];

  Future<void> initialize() async {
    // Check initial connectivity state
    final result = await _connectivity.checkConnectivity();
    _isConnected = _isConnectedState(result);

    // Listen for connectivity changes
    _connectivity.onConnectivityChanged.listen((result) {
      final wasConnected = _isConnected;
      _isConnected = _isConnectedState(result);

      // Only notify if connectivity state actually changed
      if (wasConnected != _isConnected) {
        _notifyListeners(_isConnected);
      }
    });
  }

  bool get isConnected => _isConnected;

  void addListener(void Function(bool isConnected) listener) {
    _listeners.add(listener);
  }

  void removeListener(void Function(bool isConnected) listener) {
    _listeners.remove(listener);
  }

  void _notifyListeners(bool isConnected) {
    for (final listener in _listeners) {
      listener(isConnected);
    }
  }

  bool _isConnectedState(ConnectivityResult result) {
    return result != ConnectivityResult.none;
  }

  void dispose() {
    _listeners.clear();
  }
}
