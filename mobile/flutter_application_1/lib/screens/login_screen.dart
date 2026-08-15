import 'dart:async';
import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';
import '../services/api_client.dart';
import '../services/credential_store.dart';
import 'home_screen.dart';
import '../theme/app_colors.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final LocalAuthentication _localAuth = LocalAuthentication();
  final CredentialStore _credentialStore = CredentialStore();
  bool _loading = false;
  bool _biometricAvailable = false;
  bool _hasStoredCredentials = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadBiometricAvailability();
    _loadStoredCredentialState();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await widget.apiClient.login(
        _emailController.text.trim(),
        _passwordController.text,
      );
      await _credentialStore.save(
        email: _emailController.text.trim(),
        password: _passwordController.text,
      );
      if (mounted) {
        setState(() => _hasStoredCredentials = true);
      }

      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => HomeScreen(apiClient: widget.apiClient),
        ),
      );
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
      });
    } on TimeoutException {
      setState(() {
        _error = 'Request timed out. Check network and try again.';
      });
    } catch (e) {
      setState(() {
        _error = 'Error: $e';
      });
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _loadBiometricAvailability() async {
    try {
      final available = await _localAuth.canCheckBiometrics;
      final isSupported = await _localAuth.isDeviceSupported();
      if (!mounted) {
        return;
      }
      setState(() => _biometricAvailable = available && isSupported);
    } catch (_) {
      if (mounted) {
        setState(() => _biometricAvailable = false);
      }
    }
  }

  Future<void> _loadStoredCredentialState() async {
    try {
      final credentials = await _credentialStore.read();
      if (!mounted) {
        return;
      }
      setState(() => _hasStoredCredentials = credentials != null);
    } catch (_) {
      if (mounted) {
        setState(() => _hasStoredCredentials = false);
      }
    }
  }

  Future<void> _submitWithBiometric() async {
    if (_loading) {
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final credentials = await _credentialStore.read();
      if (credentials == null) {
        throw ApiException('Sign in once with email and password to enable fingerprint login.');
      }

      final authenticated = await _localAuth.authenticate(
        localizedReason: 'Use fingerprint to sign in',
        options: const AuthenticationOptions(
          biometricOnly: true,
          stickyAuth: true,
        ),
      );
      if (!authenticated) {
        return;
      }

      await widget.apiClient.login(
        credentials['email']!,
        credentials['password']!,
      );

      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (_) => HomeScreen(apiClient: widget.apiClient),
        ),
      );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } on PlatformException catch (e) {
      setState(() => _error = _humanizeBiometricError(e));
    } catch (_) {
      setState(() => _error = 'Biometric sign in failed. Try password sign in once.');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  String _humanizeBiometricError(PlatformException e) {
    final code = e.code.toLowerCase();
    if (code.contains('notavailable')) {
      return 'Biometric is not available on this phone.';
    }
    if (code.contains('notenrolled')) {
      return 'No fingerprint is enrolled. Add one in phone settings.';
    }
    if (code.contains('lockedout')) {
      return 'Biometric is temporarily locked. Unlock phone and try again.';
    }
    if (code.contains('permanentlylockedout')) {
      return 'Biometric is locked. Use device PIN/password first, then retry.';
    }
    return 'Biometric sign in failed (${e.code}).';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      resizeToAvoidBottomInset: true,
      body: Stack(
        fit: StackFit.expand,
        children: [
          // Simplified Background
          Opacity(
            opacity: 0.6,
            child: Image.asset(
              'assets/login_bg.png',
              fit: BoxFit.cover,
              alignment: Alignment.topCenter,
              cacheWidth: 800, // Optimize memory and load time
            ),
          ),
          // Dark Gradient overlay
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.transparent,
                  Colors.black.withOpacity(0.8),
                  Colors.black,
                ],
              ),
            ),
          ),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 140),
                  Text(
                    'Driver Portal',
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                          fontWeight: FontWeight.w900,
                          color: Colors.white,
                          letterSpacing: -0.5,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Access your assigned missions and fleet data.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Colors.white.withOpacity(0.6),
                        ),
                  ),
                  const SizedBox(height: 40),
                  Form(
                    key: _formKey,
                    child: Column(
                      children: [
                        _buildField(
                          controller: _emailController,
                          label: 'Email Address',
                          icon: Icons.alternate_email_rounded,
                          type: TextInputType.emailAddress,
                        ),
                        const SizedBox(height: 16),
                        _buildField(
                          controller: _passwordController,
                          label: 'Security Password',
                          icon: Icons.lock_outline_rounded,
                          obscure: true,
                        ),
                        const SizedBox(height: 24),
                        if (_error != null)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 16),
                            child: Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.red.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: Colors.red.withOpacity(0.3)),
                              ),
                              child: Text(
                                _error!,
                                style: const TextStyle(color: Colors.redAccent, fontSize: 13, fontWeight: FontWeight.w600),
                              ),
                            ),
                          ),
                        SizedBox(
                          width: double.infinity,
                          height: 54,
                          child: ElevatedButton(
                            onPressed: _loading ? null : _submit,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              elevation: 4,
                            ),
                            child: _loading
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                                  )
                                : const Text('SECURE SIGN IN', style: TextStyle(fontWeight: FontWeight.w900, letterSpacing: 1)),
                          ),
                        ),
                        if (_biometricAvailable && _hasStoredCredentials) ...[
                          const SizedBox(height: 16),
                          SizedBox(
                            width: double.infinity,
                            height: 54,
                            child: OutlinedButton.icon(
                              onPressed: _loading ? null : _submitWithBiometric,
                              style: OutlinedButton.styleFrom(
                                foregroundColor: Colors.white,
                                side: BorderSide(color: Colors.white.withOpacity(0.3)),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                              ),
                              icon: const Icon(Icons.fingerprint_rounded),
                              label: const Text('FINGERPRINT LOGIN'),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 40),
                  Center(
                    child: Text(
                      'v1.0.5 • Secured Connection',
                      style: TextStyle(color: Colors.white.withOpacity(0.3), fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    bool obscure = false,
    TextInputType type = TextInputType.text,
  }) {
    return TextFormField(
      controller: controller,
      obscureText: obscure,
      keyboardType: type,
      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: TextStyle(color: Colors.white.withOpacity(0.5), fontSize: 13),
        prefixIcon: Icon(icon, color: Colors.white.withOpacity(0.5), size: 20),
        filled: true,
        fillColor: Colors.white.withOpacity(0.05),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: Colors.white.withOpacity(0.1)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorStyle: const TextStyle(color: Colors.redAccent),
      ),
      validator: (v) => (v == null || v.isEmpty) ? '$label is required' : null,
    );
  }
}
