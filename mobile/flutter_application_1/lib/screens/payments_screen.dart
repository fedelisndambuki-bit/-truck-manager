import 'package:flutter/material.dart';
import '../services/api_client.dart';
import '../theme/app_colors.dart';
import '../widgets/driver_header_menu.dart';
import 'payslip_preview_screen.dart';

class PaymentsScreen extends StatefulWidget {
  const PaymentsScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<PaymentsScreen> createState() => _PaymentsScreenState();
}

class _PaymentsScreenState extends State<PaymentsScreen> {
  Map<String, dynamic>? _summary;
  bool _loading = true;
  bool _downloading = false;
  String? _error;
  late String _selectedMonth;

  @override
  void initState() {
    super.initState();
    _selectedMonth = _monthKey(DateTime.now());
    _loadSummary();
  }

  Future<void> _loadSummary() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final summary = await widget.apiClient.fetchPaymentSummary();
      if (mounted) {
        setState(() => _summary = summary);
      }
    } on ApiException catch (e) {
      if (mounted) {
        setState(() => _error = e.message);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Failed to load payments.');
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _downloadPayslip() async {
    setState(() => _downloading = true);
    try {
      final path = await widget.apiClient.downloadPayslipPdf(
        month: _selectedMonth,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Payslip downloaded: ${path.split('/').last}'),
          backgroundColor: AppColors.success,
        ),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message), backgroundColor: AppColors.danger),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to download payslip.'), backgroundColor: AppColors.danger),
      );
    } finally {
      if (mounted) {
        setState(() => _downloading = false);
      }
    }
  }

  Future<void> _previewPayslip() async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PayslipPreviewScreen(
          apiClient: widget.apiClient,
          month: _selectedMonth,
        ),
      ),
    );
  }

  List<String> _last12Months() {
    final now = DateTime.now();
    return List.generate(12, (i) {
      final d = DateTime(now.year, now.month - i, 1);
      return _monthKey(d);
    });
  }

  String _monthKey(DateTime d) {
    final month = d.month.toString().padLeft(2, '0');
    return '${d.year}-$month';
  }

  String _money(dynamic value) {
    final number = value is num ? value.toDouble() : double.tryParse('${value ?? 0}') ?? 0;
    final currency = _summary?['currency'] as String? ?? 'KES';
    return '$currency ${number.toStringAsFixed(2)}';
  }

  Future<void> _showWithdrawDialog() async {
    final balance = _summary?['wallet_balance'] is num ? (_summary!['wallet_balance'] as num).toDouble() : 0.0;

    if (balance <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Your wallet balance is empty.')),
      );
      return;
    }

    final controller = TextEditingController(text: balance.toStringAsFixed(0));
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Withdraw to M-Pesa'),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Funds will be sent to your registered M-Pesa number.',
                style: TextStyle(fontSize: 12, color: AppColors.textMuted),
              ),
              const SizedBox(height: 12),
              Text('Available Balance: ${_money(balance)}', style: const TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              TextFormField(
                controller: controller,
                keyboardType: TextInputType.number,
                autofocus: true,
                decoration: const InputDecoration(
                  labelText: 'Amount to Withdraw',
                  prefixText: 'KES ',
                  border: OutlineInputBorder(),
                ),
                validator: (val) {
                  final amount = double.tryParse(val ?? '');
                  if (amount == null || amount <= 0) return 'Enter a valid amount';
                  if (amount > balance) return 'Insufficient balance';
                  if (amount < 10) return 'Minimum withdrawal is KES 10';
                  return null;
                },
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              if (formKey.currentState?.validate() ?? false) {
                final amount = double.parse(controller.text);
                Navigator.pop(context);
                await _executeWithdrawal(amount);
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Confirm Withdrawal'),
          ),
        ],
      ),
    );
  }

  Future<void> _executeWithdrawal(double amount) async {
    setState(() => _loading = true);
    try {
      final res = await widget.apiClient.requestWithdrawal(amount);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Withdrawal initiated successfully!'),
            backgroundColor: AppColors.success,
          ),
        );
      }
      await _loadSummary();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(e.toString()),
            backgroundColor: AppColors.danger,
          ),
        );
      }
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Wallet & Payments', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: const [DriverHeaderMenu()],
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: AppColors.text,
      ),
      body: RefreshIndicator(
        onRefresh: _loadSummary,
        color: AppColors.primary,
        child: _loading && _summary == null
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
                children: [
                  if (_error != null)
                    Container(
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: AppColors.danger.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline, color: AppColors.danger),
                          const SizedBox(width: 12),
                          Expanded(child: Text(_error!, style: const TextStyle(color: AppColors.danger))),
                          IconButton(onPressed: _loadSummary, icon: const Icon(Icons.refresh, color: AppColors.danger)),
                        ],
                      ),
                    ),
                  
                  _buildWalletCard(),
                  const SizedBox(height: 24),
                  
                  const Text('Monthly Earnings', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  _buildEarningsCard(),
                  
                  const SizedBox(height: 24),
                  const Text('Payslips', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  _buildPayslipActions(),
                  
                  const SizedBox(height: 24),
                  const Text('Recent Activity', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  _buildActivityList(),
                  const SizedBox(height: 40),
                ],
              ),
      ),
    );
  }

  Widget _buildWalletCard() {
    final balance = _summary?['wallet_balance'] ?? 0;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.darkSlateGrey, Color(0xFF1E3333)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppColors.darkSlateGrey.withOpacity(0.3),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Wallet Balance', style: TextStyle(color: Colors.white70, fontSize: 16)),
                  const SizedBox(height: 4),
                  Text(
                    _money(balance),
                    style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), shape: BoxShape.circle),
                child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 28),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _showWithdrawDialog(),
                  icon: const Icon(Icons.arrow_upward_rounded, size: 18),
                  label: const Text('Withdraw Funds'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: AppColors.darkSlateGrey,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 0,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEarningsCard() {
    if (_summary == null) return const SizedBox.shrink();
    
    final earnings = _summary!['earnings'] as Map<String, dynamic>? ?? {};
    final deductions = _summary!['deductions'] as Map<String, dynamic>? ?? {};
    
    // Define display labels to match the PDF payslip
    final earningLabels = {
      'standard_pay': 'Standard Pay',
      'overtime_pay': 'Overtime Pay',
      'holiday_pay': 'Holiday Pay',
      'basic_pay': 'Basic Pay',
      'commission_bonus': 'Commission & Bonus',
      'sick_pay': 'Sick Pay',
      'expenses': 'Expenses',
    };
    
    final deductionLabels = {
      'paye_tax': 'PAYE Tax',
      'national_insurance': 'National Insurance',
      'pension': 'Pension',
      'withholding_tax': 'Withholding Tax',
    };

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: AppColors.border)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Earnings', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.primary)),
            const SizedBox(height: 8),
            ...earningLabels.entries.where((e) => (earnings[e.key] ?? 0) > 0).map((e) => 
              _row(e.value, _money(earnings[e.key]))
            ).toList(),
            
            if (deductions.values.any((v) => (v ?? 0) > 0)) ...[
              const Padding(padding: EdgeInsets.symmetric(vertical: 8), child: Divider()),
              const Text('Deductions', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.danger)),
              const SizedBox(height: 8),
              ...deductionLabels.entries.where((e) => (deductions[e.key] ?? 0) > 0).map((e) => 
                _row(e.value, _money(deductions[e.key]), valueColor: AppColors.danger)
              ).toList(),
            ],
            
            const Padding(padding: EdgeInsets.symmetric(vertical: 8), child: Divider()),
            _row('Gross Salary', _money(_summary!['gross_salary']), bold: true),
            _row('Total Deductions', _money(_summary!['total_deductions']), valueColor: AppColors.danger),
            _row('Net Salary (This Month)', _money(_summary!['net_salary']), bold: true, fontSize: 18, valueColor: AppColors.primary),
          ],
        ),
      ),
    );
  }

  Widget _buildPayslipActions() {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: AppColors.border)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            DropdownButtonFormField<String>(
              value: _selectedMonth,
              decoration: InputDecoration(
                labelText: 'Select Month',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              items: _last12Months().map((m) => DropdownMenuItem(value: m, child: Text(m))).toList(),
              onChanged: (value) {
                if (value != null) setState(() => _selectedMonth = value);
              },
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _downloading ? null : _previewPayslip,
                    icon: const Icon(Icons.visibility_outlined, size: 18),
                    label: const Text('Preview'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      side: BorderSide(color: AppColors.primary),
                      foregroundColor: AppColors.primary,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _downloading ? null : _downloadPayslip,
                    icon: _downloading 
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.file_download_outlined, size: 18),
                    label: const Text('Download'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActivityList() {
    final activityData = _summary?['recent_activity'] as List?;
    if (activityData == null || activityData.isEmpty) {
      return Card(
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: AppColors.border)),
        child: const Padding(
          padding: EdgeInsets.all(20),
          child: Center(child: Text('No recent activity found.', style: TextStyle(color: AppColors.textMuted))),
        ),
      );
    }

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: AppColors.border)),
      child: Column(
        children: activityData.map((act) {
          final isCredit = act['type'] == 'credit';
          return ListTile(
            leading: CircleAvatar(
              backgroundColor: (isCredit ? AppColors.success : AppColors.danger).withOpacity(0.1),
              child: Icon(
                isCredit ? Icons.add_rounded : Icons.remove_rounded,
                color: isCredit ? AppColors.success : AppColors.danger,
              ),
            ),
            title: Text(act['title']!, style: const TextStyle(fontWeight: FontWeight.w600), maxLines: 1, overflow: TextOverflow.ellipsis),
            subtitle: Text(act['date']!, style: TextStyle(color: AppColors.textMuted, fontSize: 12)),
            trailing: Text(
              act['amount']!,
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: isCredit ? AppColors.success : AppColors.danger,
              ),
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _row(String label, String value, {bool bold = false, Color? valueColor, double fontSize = 14}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: AppColors.textMuted, fontSize: fontSize)),
          Text(
            value,
            style: TextStyle(
              fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
              color: valueColor ?? AppColors.text,
              fontSize: fontSize,
            ),
          ),
        ],
      ),
    );
  }
}
