import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../widgets/driver_header_menu.dart';

class AiFeedbackScreen extends StatelessWidget {
  const AiFeedbackScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('AI Feedback'),
        actions: const [DriverHeaderMenu()],
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: apiClient.fetchAiFeedback(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return const Center(child: Text('Failed to load AI feedback.'));
          }
          final message = snapshot.data?['message']?.toString() ?? 'No feedback.';
          return Padding(
            padding: const EdgeInsets.all(16),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Text(message),
              ),
            ),
          );
        },
      ),
    );
  }
}
