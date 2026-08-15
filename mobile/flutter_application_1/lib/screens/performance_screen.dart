import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../widgets/driver_header_menu.dart';
import '../theme/app_colors.dart';

class PerformanceScreen extends StatelessWidget {
  const PerformanceScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Performance'),
        actions: const [DriverHeaderMenu()],
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: apiClient.fetchPerformance(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return const Center(child: Text('Failed to load performance.'));
          }
          final data = snapshot.data ?? {};
          return Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                _scoreCard(
                  context,
                  title: 'Performance Score',
                  value: data['performance_score']?.toString() ?? '0',
                  color: AppColors.success,
                ),
                const SizedBox(height: 16),
                _scoreCard(
                  context,
                  title: 'Risk Score',
                  value: data['risk_score']?.toString() ?? '0',
                  color: AppColors.warning,
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _scoreCard(
    BuildContext context, {
    required String title,
    required String value,
    required Color color,
  }) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(Icons.speed, color: color),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: Theme.of(context).textTheme.bodyMedium),
                  const SizedBox(height: 6),
                  Text(
                    value,
                    style: Theme.of(context)
                        .textTheme
                        .headlineSmall
                        ?.copyWith(fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
