import 'package:flutter/material.dart';
import '../services/driver_profile_store.dart';
import '../services/theme_mode_store.dart';
import '../theme/app_colors.dart';

class DriverHeaderMenu extends StatefulWidget {
  const DriverHeaderMenu({super.key});

  @override
  State<DriverHeaderMenu> createState() => _DriverHeaderMenuState();
}

class _DriverHeaderMenuState extends State<DriverHeaderMenu> {
  final DriverProfileStore _profileStore = DriverProfileStore();
  Map<String, dynamic>? _profile;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    final profile = await _profileStore.read();
    if (mounted) {
      setState(() => _profile = profile);
    }
  }

  @override
  Widget build(BuildContext context) {
    final imageUrl = _profile?['driver_profile_url'] as String?;
    return PopupMenuButton<String>(
      tooltip: 'Profile menu',
      onOpened: _loadProfile,
      onSelected: (value) {
        switch (value) {
          case 'dark':
            ThemeModeStore.setDark();
            break;
          case 'light':
            ThemeModeStore.setLight();
            break;
          case 'settings':
            Navigator.of(context).pushNamed('/profile');
            break;
        }
      },
      itemBuilder: (context) => const [
        PopupMenuItem(value: 'dark', child: Text('Dark mode')),
        PopupMenuItem(value: 'light', child: Text('Light mode')),
        PopupMenuItem(value: 'settings', child: Text('Settings')),
      ],
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12),
        child: CircleAvatar(
          radius: 16,
          backgroundColor: Colors.white,
          backgroundImage: (imageUrl != null && imageUrl.isNotEmpty) ? NetworkImage(imageUrl) : null,
          child: (imageUrl == null || imageUrl.isEmpty)
              ? const Icon(Icons.person, color: AppColors.darkSlateGrey, size: 18)
              : null,
        ),
      ),
    );
  }
}
