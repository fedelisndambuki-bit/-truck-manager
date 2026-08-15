import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../theme/app_colors.dart';
import '../widgets/driver_header_menu.dart';

class TyreManagementScreen extends StatefulWidget {
  const TyreManagementScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<TyreManagementScreen> createState() => _TyreManagementScreenState();
}

class _TyreManagementScreenState extends State<TyreManagementScreen> {
  bool _loading = true;
  List<dynamic> _trucks = [];
  List<dynamic> _availableTyres = [];
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _loading = true);
    try {
      final data = await widget.apiClient.fetchTyreData();
      setState(() {
        _trucks = data['trucks'];
        _availableTyres = data['available_tyres'];
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _installTyre(int truckId, String position, int tyreId) async {
    try {
      await widget.apiClient.installTyre(
        truckId: truckId,
        position: position,
        tyreId: tyreId,
      );
      _loadData();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tyre installed successfully')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to install tyre: $e')),
        );
      }
    }
  }

  Future<void> _removeTyre(int tyreId) async {
    try {
      await widget.apiClient.removeTyre(tyreId: tyreId);
      _loadData();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tyre removed successfully')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to remove tyre: $e')),
        );
      }
    }
  }

  void _showInstallDialog(int truckId, String position) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        height: MediaQuery.of(context).size.height * 0.7,
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Text(
                'Install Tyre at $position',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
            ),
            const Divider(),
            Expanded(
              child: _availableTyres.isEmpty
                  ? const Center(child: Text('No available spare tyres'))
                  : ListView.builder(
                      itemCount: _availableTyres.length,
                      itemBuilder: (context, index) {
                        final tyre = _availableTyres[index];
                        return ListTile(
                          leading: const Icon(Icons.circle_outlined),
                          title: Text('${tyre['brand']} - ${tyre['serial_number']}'),
                          subtitle: Text('Size: ${tyre['size']} | Depth: ${tyre['tread_depth']}mm'),
                          onTap: () {
                            Navigator.pop(context);
                            _installTyre(truckId, position, tyre['id']);
                          },
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Tyre Management'),
        actions: const [DriverHeaderMenu()],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text('Error: $_error'))
              : RefreshIndicator(
                  onRefresh: _loadData,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      ..._trucks.map((truck) => _buildTruckCard(truck)),
                      const SizedBox(height: 20),
                      _buildInventorySection(),
                    ],
                  ),
                ),
    );
  }

  Widget _buildTruckCard(Map<String, dynamic> truck) {
    final axleConfig = truck['axle_config'] ?? '2-axle';
    final installedTyres = truck['installed_tyres'] as List<dynamic>;

    return Card(
      elevation: 4,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: const BoxDecoration(
              color: AppColors.darkSlateGrey,
              borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      truck['plate_number'],
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    Text(
                      '${truck['brand']} ${truck['model']}',
                      style: const TextStyle(color: Colors.white70, fontSize: 12),
                    ),
                  ],
                ),
                const Icon(Icons.local_shipping, color: Colors.white),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: _buildTruckLayout(truck['id'], axleConfig, installedTyres, truck['tyre_count'] ?? 6),
          ),
        ],
      ),
    );
  }

  Widget _buildTruckLayout(int truckId, String axleConfig, List<dynamic> installedTyres, int totalTyres) {
    // Exact logic from web: axleConfig like '2-axle'
    int axleCount = int.tryParse(axleConfig.split('-')[0]) ?? 2;

    return Column(
      children: List.generate(axleCount, (index) {
        int axleIndex = index + 1;
        return _buildAxleRow(truckId, axleIndex, axleCount, totalTyres, installedTyres);
      }),
    );
  }

  Widget _buildAxleRow(int truckId, int axleIndex, int axleCount, int totalTyres, List<dynamic> installedTyres) {
    // Web logic: Axle 1 is steering (1 per side), others depend on tyre_count
    int tyresPerSide = 1;
    if (axleIndex > 1) {
      int remainingTyres = totalTyres - 2;
      int driveAxles = axleCount - 1;
      if (driveAxles > 0) {
        double perAxleSide = (remainingTyres / driveAxles) / 2;
        tyresPerSide = perAxleSide >= 2 ? 2 : 1;
      }
    }

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Column(
        children: [
          Text(
            'Axle $axleIndex ${axleIndex == 1 ? "(Steering)" : "(Drive)"}',
            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.textMuted),
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Left side
              Row(
                children: List.generate(tyresPerSide, (tIndex) {
                  final pos = 'L$axleIndex${tyresPerSide > 1 ? (tIndex == 0 ? "i" : "o") : ""}';
                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 2),
                    child: _buildTyreSlot(truckId, pos, installedTyres),
                  );
                }),
              ),
              Container(height: 2, width: 40, color: Colors.grey[400]), // Axle line
              // Right side
              Row(
                children: List.generate(tyresPerSide, (tIndex) {
                  final pos = 'R$axleIndex${tyresPerSide > 1 ? (tIndex == 0 ? "i" : "o") : ""}';
                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 2),
                    child: _buildTyreSlot(truckId, pos, installedTyres),
                  );
                }),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTyreSlot(int truckId, String position, List<dynamic> installedTyres) {
    final installedTyre = installedTyres.firstWhere(
      (t) => t['position'] == position,
      orElse: () => null,
    );

    return GestureDetector(
      onTap: () {
        if (installedTyre == null) {
          _showInstallDialog(truckId, position);
        } else {
          _showTyreActionMenu(installedTyre);
        }
      },
      child: Container(
        width: 40,
        height: 65,
        decoration: BoxDecoration(
          color: installedTyre != null ? AppColors.darkSlateGrey : Colors.white,
          borderRadius: BorderRadius.circular(4),
          border: Border.all(
            color: installedTyre != null ? Colors.black : Colors.grey[300]!,
            width: 1.5,
          ),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (installedTyre != null) ...[
              const Icon(Icons.circle, color: Colors.white, size: 12),
              const SizedBox(height: 4),
              Text(
                position,
                style: const TextStyle(color: Colors.white70, fontSize: 7, fontWeight: FontWeight.bold),
              ),
              Text(
                installedTyre['serial_number'].toString().substring(
                  (installedTyre['serial_number'].toString().length - 4).clamp(0, installedTyre['serial_number'].toString().length),
                ),
                style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
              ),
            ] else ...[
              Text(
                position,
                style: TextStyle(color: Colors.grey[400], fontSize: 8, fontWeight: FontWeight.bold),
              ),
              Icon(Icons.add, color: Colors.grey[300], size: 16),
            ],
          ],
        ),
      ),
    );
  }

  Future<void> _discardTyre(int tyreId) async {
    try {
      await widget.apiClient.discardTyre(tyreId: tyreId);
      _loadData();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Tyre marked as scrapped')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to discard tyre: $e')),
        );
      }
    }
  }

  void _showTyreDetailModal(Map<String, dynamic> tyre) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        height: MediaQuery.of(context).size.height * 0.75,
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: const BoxDecoration(
                color: AppColors.darkSlateGrey,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.circle_outlined, color: Colors.white70, size: 20),
                      const SizedBox(width: 10),
                      Text(
                        'Tyre: ${tyre['serial_number']}',
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                    ],
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, color: Colors.white),
                  ),
                ],
              ),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    GridView.count(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      crossAxisCount: 2,
                      childAspectRatio: 2.5,
                      mainAxisSpacing: 16,
                      crossAxisSpacing: 16,
                      children: [
                        _infoItem('Brand', tyre['brand'] ?? '—'),
                        _infoItem('Size', tyre['size'] ?? '—'),
                        _infoItem('Type', tyre['type'] ?? '—'),
                        _infoItem('Tread Depth', '${tyre['tread_depth']}mm'),
                        _infoItem('Pressure', tyre['pressure'] != null ? '${tyre['pressure']} PSI' : '—'),
                        _infoItem('Retreads', '${tyre['retread_count'] ?? 0}'),
                        _infoItem('Cost', '${tyre['purchase_cost'] ?? '—'}'),
                        _infoItem('Purchase Date', tyre['purchase_date'] ?? '—'),
                      ],
                    ),
                    const SizedBox(height: 24),
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: () {
                              Navigator.pop(context);
                              _showRemovalConfirmation(tyre);
                            },
                            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
                            icon: const Icon(Icons.remove_circle_outline, color: Colors.white),
                            label: const Text('Uninstall', style: TextStyle(color: Colors.white)),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: ElevatedButton.icon(
                            onPressed: () => Navigator.pop(context),
                            style: ElevatedButton.styleFrom(backgroundColor: AppColors.darkSlateGrey),
                            icon: const Icon(Icons.check, color: Colors.white),
                            label: const Text('Done', style: TextStyle(color: Colors.white)),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _infoItem(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label.toUpperCase(),
          style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black87),
        ),
      ],
    );
  }

  void _showRemovalConfirmation(Map<String, dynamic> tyre) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.report_problem_outlined, size: 48, color: Colors.orange),
            const SizedBox(height: 16),
            const Text(
              'Assess Tyre Condition',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              'What is the condition of this tyre after removal?',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () {
                      Navigator.pop(context);
                      _removeTyre(tyre['id']);
                    },
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.green.shade200),
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        children: [
                          const Icon(Icons.check_circle_outline, color: Colors.green),
                          const SizedBox(height: 8),
                          const Text('Good Condition', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                          Text('Move to Spares', style: TextStyle(fontSize: 10, color: Colors.green.shade700)),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: InkWell(
                    onTap: () {
                      Navigator.pop(context);
                      _discardTyre(tyre['id']);
                    },
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.red.shade200),
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        children: [
                          const Icon(Icons.cancel_outlined, color: Colors.red),
                          const SizedBox(height: 8),
                          const Text('Bad / Worn Out', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                          Text('Move to Scrapped', style: TextStyle(fontSize: 10, color: Colors.red.shade700)),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Cancel Removal', style: TextStyle(color: Colors.grey)),
            ),
          ],
        ),
      ),
    );
  }

  void _showTyreActionMenu(Map<String, dynamic> tyre) {
    _showTyreDetailModal(tyre);
  }

  Widget _buildInventorySection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Available Spare Tyres',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 8),
        _availableTyres.isEmpty
            ? const Center(child: Padding(
                padding: EdgeInsets.all(20.0),
                child: Text('No spare tyres in inventory'),
              ))
            : ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: _availableTyres.length,
                itemBuilder: (context, index) {
                  final tyre = _availableTyres[index];
                  return Card(
                    child: ListTile(
                      leading: const Icon(Icons.circle_notifications),
                      title: Text(tyre['serial_number']),
                      subtitle: Text('${tyre['brand']} | ${tyre['size']}'),
                      trailing: Text('${tyre['tread_depth']}mm'),
                    ),
                  );
                },
              ),
      ],
    );
  }
}
