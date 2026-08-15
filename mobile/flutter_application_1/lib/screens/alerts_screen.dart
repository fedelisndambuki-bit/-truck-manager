import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../widgets/driver_header_menu.dart';

class AlertsScreen extends StatefulWidget {
  const AlertsScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<AlertsScreen> createState() => _AlertsScreenState();
}

class _AlertsScreenState extends State<AlertsScreen> {
  Map<String, dynamic>? _cachedData;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    if (_loading) return;
    setState(() {
      _loading = _cachedData == null;
      _error = null;
    });
    try {
      final data = await widget.apiClient.fetchAlerts();
      if (mounted) {
        setState(() {
          _cachedData = data;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Alerts'),
        actions: const [DriverHeaderMenu()],
      ),
      body: RefreshIndicator(
        onRefresh: _loadData,
        child: _buildContent(),
      ),
    );
  }

  Widget _buildContent() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null && _cachedData == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Text('Failed to load alerts: $_error', textAlign: TextAlign.center),
        ),
      );
    }
    
    final alerts = (_cachedData?['alerts'] as List<dynamic>?) ?? [];
    if (alerts.isEmpty) {
      return const Center(child: Text('No alerts.'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: alerts.length,
      itemBuilder: (context, index) {
        final alert = alerts[index] as Map<String, dynamic>;
        final type = alert['type']?.toString() ?? 'info';
        final severity = alert['severity']?.toString() ?? 'info';
        
        IconData icon;
        Color color;
        
        switch(type) {
          case 'announcement':
            icon = Icons.campaign_rounded;
            color = Colors.blue;
            break;
          case 'safety':
            icon = Icons.security_rounded;
            color = Colors.orange;
            break;
          case 'maintenance':
            icon = Icons.build_rounded;
            color = severity == 'danger' ? Colors.red : Colors.blueGrey;
            break;
          default:
            icon = Icons.notifications_rounded;
            color = Colors.grey;
        }

        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          child: ListTile(
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            leading: CircleAvatar(
              backgroundColor: color.withOpacity(0.1),
              child: Icon(icon, color: color),
            ),
            title: Text(
              alert['title']?.toString() ?? 'Alert',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 4),
                Text(alert['message']?.toString() ?? ''),
                if (alert['created_at'] != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    _formatDate(alert['created_at']),
                    style: TextStyle(fontSize: 10, color: Colors.grey.shade500),
                  ),
                ]
              ],
            ),
          ),
        );
      },
    );
  }

  String _formatDate(String isoDate) {
    try {
      final dt = DateTime.parse(isoDate).toLocal();
      return '${dt.day}/${dt.month}/${dt.year} ${dt.hour}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return '';
    }
  }
}
