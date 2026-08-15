import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'package:record/record.dart';
import 'package:audioplayers/audioplayers.dart';
import 'package:emoji_picker_flutter/emoji_picker_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';
import '../services/api_client.dart';
import '../services/driver_profile_store.dart';
import '../theme/app_colors.dart';
import '../widgets/driver_header_menu.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _controller = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final DriverProfileStore _profileStore = DriverProfileStore();
  final ImagePicker _imagePicker = ImagePicker();
  final AudioRecorder _audioRecorder = AudioRecorder();
  final AudioPlayer _audioPlayer = AudioPlayer();

  List<Map<String, dynamic>> _messages = [];
  Map<String, dynamic>? _profile;
  bool _loading = true;
  bool _sending = false;
  bool _recordingVoiceNote = false;
  bool _showEmojiPicker = false;
  String? _playingVoiceUrl;
  String? _activeCallId;
  String? _activeCallUrl;
  WebViewController? _callWebViewController;
  String? _lastHandledIncomingCallId;
  Timer? _ringTimer;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _loadProfile();
    _loadMessages();
    _audioPlayer.onPlayerComplete.listen((_) {
      if (mounted) {
        setState(() => _playingVoiceUrl = null);
      }
    });
    _pollTimer = Timer.periodic(
      const Duration(seconds: 10),
      (_) => _loadMessages(silent: true),
    );
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _stopRingtone();
    _audioPlayer.dispose();
    _audioRecorder.dispose();
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _startRingtone() {
    if (_ringTimer != null) {
      return;
    }
    SystemSound.play(SystemSoundType.alert);
    _ringTimer = Timer.periodic(const Duration(milliseconds: 1200), (_) {
      SystemSound.play(SystemSoundType.alert);
    });
  }

  void _stopRingtone() {
    _ringTimer?.cancel();
    _ringTimer = null;
  }

  Future<void> _loadProfile() async {
    final p = await _profileStore.read();
    if (mounted) {
      setState(() => _profile = p);
    }
  }

  Future<void> _loadMessages({bool silent = false}) async {
    if (!silent) {
      setState(() => _loading = true);
    }
    try {
      final items = await widget.apiClient.fetchChatMessages();
      if (mounted) {
        setState(() => _messages = items);
        _processCallState(items);
        _scrollToBottom();
      }
    } finally {
      if (mounted && !silent) {
        setState(() => _loading = false);
      }
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollController.hasClients) {
        return;
      }
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent + 80,
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOut,
      );
    });
  }

  void _onEmojiSelected(Emoji emoji) {
    final text = _controller.text;
    final selection = _controller.selection;
    final start = selection.start >= 0 ? selection.start : text.length;
    final end = selection.end >= 0 ? selection.end : text.length;
    final newText = text.replaceRange(start, end, emoji.emoji);
    final newCursor = start + emoji.emoji.length;
    _controller.value = TextEditingValue(
      text: newText,
      selection: TextSelection.collapsed(offset: newCursor),
    );
  }

  Future<void> _send() async {
    final text = _controller.text.trim();
    if (text.isEmpty || _sending) {
      return;
    }
    setState(() => _sending = true);
    try {
      await widget.apiClient.sendChatMessage(text);
      _controller.clear();
      await _loadMessages(silent: true);
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  Future<void> _pickAndSendVoiceNote() async {
    if (_sending) {
      return;
    }

    if (_recordingVoiceNote) {
      await _stopAndSendVoiceNote();
      return;
    }

    try {
      final hasPermission = await _audioRecorder.hasPermission();
      if (!hasPermission) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Microphone permission is required.')),
        );
        return;
      }

      final dir = await getTemporaryDirectory();
      final path = '${dir.path}${Platform.pathSeparator}chat-voice-${DateTime.now().millisecondsSinceEpoch}.m4a';
      await _audioRecorder.start(const RecordConfig(encoder: AudioEncoder.aacLc), path: path);

      if (mounted) {
        setState(() => _recordingVoiceNote = true);
      }
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not start voice recording.')),
      );
    }
  }

  Future<void> _stopAndSendVoiceNote() async {
    setState(() {
      _recordingVoiceNote = false;
      _sending = true;
    });
    try {
      final path = await _audioRecorder.stop();
      if (path == null || path.isEmpty) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No voice note captured. Try again.')),
        );
        return;
      }
      final file = File(path);
      if (!file.existsSync() || file.lengthSync() == 0) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Voice note is empty. Record again.')),
        );
        return;
      }
      final message = _controller.text.trim();
      await widget.apiClient.sendChatVoiceNote(
        file,
        message: message.isEmpty ? null : message,
      );
      _controller.clear();
      await _loadMessages(silent: true);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Voice note upload failed.')),
      );
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  Future<void> _toggleVoicePlayback(String url) async {
    try {
      if (_playingVoiceUrl == url) {
        await _audioPlayer.stop();
        if (mounted) {
          setState(() => _playingVoiceUrl = null);
        }
        return;
      }

      await _audioPlayer.stop();
      await _audioPlayer.play(UrlSource(url));
      if (mounted) {
        setState(() => _playingVoiceUrl = url);
      }
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Unable to play voice note.')),
      );
    }
  }

  Future<void> _openUrl(String url, {String error = 'Could not open link.'}) async {
    final uri = Uri.tryParse(url);
    if (uri == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
      return;
    }
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
    }
  }

  Map<String, dynamic>? _parseCallEvent(String text) {
    if (!text.startsWith('CALL_EVENT::')) {
      return null;
    }
    try {
      final payload = jsonDecode(text.substring(12));
      if (payload is Map<String, dynamic>) {
        return payload;
      }
      if (payload is Map) {
        return Map<String, dynamic>.from(payload);
      }
    } catch (_) {
      return null;
    }
    return null;
  }

  String _callEventLabel(Map<String, dynamic> event) {
    switch ((event['type'] ?? '').toString()) {
      case 'request':
        return 'Started a call';
      case 'accept':
        return 'Accepted the call';
      case 'decline':
        return 'Declined the call';
      case 'end':
        return 'Ended the call';
      default:
        return 'Call event';
    }
  }

  Map<String, dynamic> _buildCallState(List<Map<String, dynamic>> messages) {
    final Map<String, Map<String, dynamic>> calls = {};
    var incoming = <String, dynamic>{};
    var active = <String, dynamic>{};

    for (final m in messages) {
      final text = (m['message'] as String? ?? '');
      final event = _parseCallEvent(text);
      if (event == null) {
        continue;
      }

      final callId = (event['call_id'] ?? '').toString();
      if (callId.isEmpty) {
        continue;
      }
      final senderType = (m['sender_type'] ?? '').toString();
      calls.putIfAbsent(callId, () => {
            'call_id': callId,
            'url': null,
            'request_sender': null,
            'accepted': false,
            'declined': false,
            'ended': false,
            'order': 0,
          });
      final c = calls[callId]!;
      c['order'] = (m['id'] as int?) ?? c['order'];
      final type = (event['type'] ?? '').toString();
      if (type == 'request') {
        c['url'] = event['url'];
        c['request_sender'] = senderType;
      } else if (type == 'accept') {
        c['accepted'] = true;
      } else if (type == 'decline') {
        c['declined'] = true;
      } else if (type == 'end') {
        c['ended'] = true;
      }
    }

    final sorted = calls.values.toList()
      ..sort((a, b) => ((b['order'] as int?) ?? 0).compareTo((a['order'] as int?) ?? 0));
    for (final c in sorted) {
      if (c['declined'] == true || c['ended'] == true) {
        continue;
      }
      if (c['accepted'] == true) {
        active = c;
        break;
      }
      if (c['request_sender'] == 'company') {
        incoming = c;
        break;
      }
    }

    return {'incoming': incoming, 'active': active};
  }

  void _processCallState(List<Map<String, dynamic>> items) {
    final callState = _buildCallState(items);
    final incoming = callState['incoming'] as Map<String, dynamic>;
    final active = callState['active'] as Map<String, dynamic>;

    if (incoming.isNotEmpty) {
      final incomingCallId = (incoming['call_id'] ?? '').toString();
      if (incomingCallId.isNotEmpty &&
          incomingCallId != _lastHandledIncomingCallId &&
          incomingCallId != _activeCallId) {
        _lastHandledIncomingCallId = incomingCallId;
        _showIncomingCallDialog(
          callId: incomingCallId,
          url: (incoming['url'] ?? '').toString(),
        );
      }
    }

    if (active.isNotEmpty) {
      _stopRingtone();
      final callId = (active['call_id'] ?? '').toString();
      final url = (active['url'] ?? '').toString();
      if (callId.isNotEmpty && url.isNotEmpty && (_activeCallId != callId || _activeCallUrl != url)) {
        final role = (active['request_sender'] ?? '') == 'driver' ? 'caller' : 'callee';
        _openCallInChat(callId: callId, url: url, role: role);
      }
      return;
    }

    if (_activeCallId != null) {
      setState(() {
        _activeCallId = null;
        _activeCallUrl = null;
        _callWebViewController = null;
      });
    }
  }

  Future<void> _sendCallEvent({
    required String type,
    required String callId,
    String? url,
  }) async {
    final payload = <String, dynamic>{
      'type': type,
      'call_id': callId,
      'from': 'driver',
      if (url != null && url.isNotEmpty) 'url': url,
    };
    await widget.apiClient.sendChatMessage('CALL_EVENT::${jsonEncode(payload)}');
  }

  Future<void> _showIncomingCallDialog({
    required String callId,
    required String url,
  }) async {
    if (!mounted) return;
    _startRingtone();
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (context) {
        return AlertDialog(
          title: const Text('Incoming Call'),
          content: const Text('Company is calling you through chat.'),
          actions: [
            TextButton(
              onPressed: () async {
                _stopRingtone();
                Navigator.of(context).pop();
                await _sendCallEvent(type: 'decline', callId: callId);
                await _loadMessages(silent: true);
              },
              child: const Text('Decline'),
            ),
            ElevatedButton(
              onPressed: () async {
                _stopRingtone();
                Navigator.of(context).pop();
                await _sendCallEvent(type: 'accept', callId: callId);
                _openCallInChat(callId: callId, url: url, role: 'callee');
              },
              child: const Text('Accept'),
            ),
          ],
        );
      },
    );
    _stopRingtone();
  }

  void _openCallInChat({
    required String callId,
    required String url,
    required String role,
  }) {
    final withQuery = url.contains('?')
        ? '$url&role=$role&call_id=$callId'
        : '$url?role=$role&call_id=$callId';
    final params = const PlatformWebViewControllerCreationParams();
    final controller = WebViewController.fromPlatformCreationParams(params)
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..loadRequest(Uri.parse(withQuery));

    final platform = controller.platform;
    if (platform is AndroidWebViewController) {
      platform.setMediaPlaybackRequiresUserGesture(false);
      platform.setOnPlatformPermissionRequest((request) {
        request.grant();
      });
    }

    if (mounted) {
      setState(() {
        _activeCallId = callId;
        _activeCallUrl = url;
        _callWebViewController = controller;
      });
    }
  }

  Future<void> _endActiveCall() async {
    final callId = _activeCallId;
    if (callId == null || callId.isEmpty) {
      return;
    }
    await _sendCallEvent(type: 'end', callId: callId);
    if (mounted) {
      setState(() {
        _activeCallId = null;
        _activeCallUrl = null;
        _callWebViewController = null;
      });
    }
    _stopRingtone();
  }

  Future<void> _sendCallInvite() async {
    if (_sending) {
      return;
    }
    final suffix = DateTime.now().millisecondsSinceEpoch;
    final room = 'ShiftHubDriver$suffix';
    final configured = await widget.apiClient.getConfiguredOrigin();
    final hostOrigin = _originFromConfigured(configured);
    final callId = '${DateTime.now().millisecondsSinceEpoch}${room.hashCode.abs()}';
    final url = '$hostOrigin/calls/$room?role=caller&call_id=$callId';

    setState(() => _sending = true);
    try {
      await _sendCallEvent(type: 'request', callId: callId, url: url);
      await _loadMessages(silent: true);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Call invite sent.')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  String _originFromConfigured(String configured) {
    final uri = Uri.tryParse(configured.trim());
    if (uri == null || uri.host.isEmpty) {
      return configured;
    }
    final port = uri.hasPort ? ':${uri.port}' : '';
    return '${uri.scheme}://${uri.host}$port';
  }

  Future<void> _pickAndSendAttachment() async {
    if (_sending) {
      return;
    }

    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined),
              title: const Text('Take Photo'),
              onTap: () => Navigator.of(context).pop(ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Choose from Gallery'),
              onTap: () => Navigator.of(context).pop(ImageSource.gallery),
            ),
          ],
        ),
      ),
    );

    if (source == null) {
      return;
    }

    final picked = await _imagePicker.pickImage(source: source, imageQuality: 85);
    if (picked == null) {
      return;
    }

    setState(() => _sending = true);
    try {
      final message = _controller.text.trim();
      await widget.apiClient.sendChatAttachment(
        File(picked.path),
        message: message.isEmpty ? null : message,
      );
      _controller.clear();
      await _loadMessages(silent: true);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  Future<void> _showMessageActions(Map<String, dynamic> message) async {
    final senderType = (message['sender_type'] ?? '').toString();
    if (senderType != 'driver') {
      return;
    }
    final text = (message['message'] ?? '').toString();
    if (text.startsWith('CALL_EVENT::')) {
      return;
    }
    if ((message['deleted_for_everyone_at'] as String?) != null) {
      return;
    }

    final messageId = message['id'];
    if (messageId is! int) {
      return;
    }

    await showModalBottomSheet<void>(
      context: context,
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: const Icon(Icons.edit_outlined),
                title: const Text('Edit Message'),
                onTap: () async {
                  Navigator.of(context).pop();
                  await _editMessageDialog(messageId, (message['message'] ?? '').toString());
                },
              ),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.delete_outline),
                title: const Text('Delete for Me'),
                onTap: () async {
                  Navigator.of(context).pop();
                  await _deleteMessage(messageId, deleteForEveryone: false);
                },
              ),
              ListTile(
                leading: const Icon(Icons.delete_forever_outlined),
                title: const Text('Delete for Everyone'),
                onTap: () async {
                  Navigator.of(context).pop();
                  await _deleteMessage(messageId, deleteForEveryone: true);
                },
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _editMessageDialog(int messageId, String current) async {
    final controller = TextEditingController(text: current);
    try {
      final updated = await showDialog<String>(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Edit Message'),
          content: TextField(
            controller: controller,
            maxLines: 3,
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(controller.text.trim()),
              child: const Text('Save'),
            ),
          ],
        ),
      );
      if (updated == null || updated.isEmpty) {
        return;
      }
      await widget.apiClient.editChatMessage(messageId: messageId, message: updated);
      await _loadMessages(silent: true);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      controller.dispose();
    }
  }

  Future<void> _deleteMessage(int messageId, {required bool deleteForEveryone}) async {
    try {
      await widget.apiClient.deleteChatMessage(
        messageId: messageId,
        deleteForEveryone: deleteForEveryone,
      );
      await _loadMessages(silent: true);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Widget _avatar(String? url, {required bool isDriver}) {
    if (url != null && url.isNotEmpty) {
      return CircleAvatar(radius: 12, backgroundImage: NetworkImage(url));
    }
    return CircleAvatar(
      radius: 12,
      backgroundColor: isDriver ? AppColors.primary : AppColors.darkSlateGrey,
      child: Icon(
        isDriver ? Icons.person : Icons.business,
        size: 14,
        color: Colors.white,
      ),
    );
  }

  Widget _tick(Map<String, dynamic> m) {
    final readAt = m['read_at'] as String?;
    if (readAt == null || readAt.isEmpty) {
      return const Icon(Icons.check, size: 14, color: Colors.grey);
    }
    return const Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(Icons.check, size: 14, color: Colors.green),
        Icon(Icons.check, size: 14, color: Colors.green),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Chat'),
        actions: const [DriverHeaderMenu()],
      ),
      body: Column(
        children: [
          if (_activeCallUrl != null && _callWebViewController != null)
            Container(
              height: 260,
              margin: const EdgeInsets.fromLTRB(10, 10, 10, 0),
              decoration: BoxDecoration(
                border: Border.all(color: const Color(0xFFCBD5E1)),
                borderRadius: BorderRadius.circular(10),
                color: Colors.white,
              ),
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                    decoration: const BoxDecoration(
                      color: Color(0xFF0F172A),
                      borderRadius: BorderRadius.vertical(top: Radius.circular(9)),
                    ),
                    child: Row(
                      children: [
                        const Expanded(
                          child: Text(
                            'Call In Progress',
                            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                          ),
                        ),
                        TextButton(
                          onPressed: _endActiveCall,
                          child: const Text('End', style: TextStyle(color: Colors.white)),
                        ),
                      ],
                    ),
                  ),
                  Expanded(child: WebViewWidget(controller: _callWebViewController!)),
                ],
              ),
            ),
          Expanded(
            child: Stack(
              children: [
                const Positioned.fill(child: _ChatPatternBackground()),
                if (_loading)
                  const Center(child: CircularProgressIndicator())
                else
                  ListView.builder(
                    controller: _scrollController,
                    padding: const EdgeInsets.all(10),
                    itemCount: _messages.length,
                    itemBuilder: (context, index) {
                      final m = _messages[index];
                      final isDriver =
                          (m['sender_type'] as String? ?? '') == 'driver';
                      final text = m['message'] as String? ?? '';
                      final callEvent = _parseCallEvent(text);
                      final displayText = callEvent != null ? _callEventLabel(callEvent) : text;
                      final voiceNoteUrl = m['voice_note_url'] as String?;
                      final attachmentUrl = m['attachment_url'] as String?;
                      final attachmentName = m['attachment_name'] as String?;
                      final senderName =
                          m['sender_name'] as String? ??
                          (isDriver ? 'Driver' : 'Company');
                      final profileUrl = m['sender_profile_url'] as String?;

                      return Align(
                        alignment: isDriver
                            ? Alignment.centerRight
                            : Alignment.centerLeft,
                        child: Row(
                          mainAxisAlignment: isDriver
                              ? MainAxisAlignment.end
                              : MainAxisAlignment.start,
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            if (!isDriver) ...[
                              _avatar(profileUrl, isDriver: false),
                              const SizedBox(width: 6),
                            ],
                            Flexible(
                              child: ConstrainedBox(
                                constraints: BoxConstraints(
                                  maxWidth:
                                      MediaQuery.of(context).size.width * 0.66,
                                ),
                              child: GestureDetector(
                                  onLongPress: () => _showMessageActions(m),
                                  child: Container(
                                    margin: const EdgeInsets.symmetric(
                                      vertical: 4,
                                    ),
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 10,
                                      vertical: 7,
                                    ),
                                    decoration: BoxDecoration(
                                      color: isDriver
                                          ? const Color(0xFFD9FDD3)
                                          : Colors.white,
                                      borderRadius: BorderRadius.circular(12),
                                      boxShadow: const [
                                        BoxShadow(
                                          color: Colors.black12,
                                          blurRadius: 2,
                                          offset: Offset(0, 1),
                                        ),
                                      ],
                                    ),
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                      Text(
                                        senderName,
                                        style: const TextStyle(
                                          fontSize: 11,
                                          color: AppColors.textMuted,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(
                                        displayText,
                                        style: const TextStyle(fontSize: 12),
                                      ),
                                      if ((m['edited_at'] as String?) != null)
                                        const Text(
                                          '(edited)',
                                          style: TextStyle(fontSize: 10, color: AppColors.textMuted),
                                        ),
                                      if (voiceNoteUrl != null && voiceNoteUrl.isNotEmpty) ...[
                                        const SizedBox(height: 6),
                                        Container(
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 8,
                                            vertical: 6,
                                          ),
                                          decoration: BoxDecoration(
                                            color: Colors.black.withValues(alpha: 0.06),
                                            borderRadius: BorderRadius.circular(8),
                                          ),
                                          child: Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              IconButton(
                                                visualDensity: VisualDensity.compact,
                                                padding: EdgeInsets.zero,
                                                constraints: const BoxConstraints(),
                                                onPressed: () => _toggleVoicePlayback(voiceNoteUrl),
                                                icon: Icon(
                                                  _playingVoiceUrl == voiceNoteUrl
                                                      ? Icons.stop_circle_outlined
                                                      : Icons.play_circle_outline,
                                                  size: 16,
                                                ),
                                              ),
                                              const SizedBox(width: 6),
                                              const Text(
                                                'Voice note attached',
                                                style: TextStyle(fontSize: 11),
                                              ),
                                              const SizedBox(width: 6),
                                              InkWell(
                                                onTap: () async {
                                                  await _openUrl(
                                                    voiceNoteUrl,
                                                    error: 'Could not open voice note.',
                                                  );
                                                },
                                                child: const Text(
                                                  'Open',
                                                  style: TextStyle(
                                                    fontSize: 11,
                                                    fontWeight: FontWeight.w700,
                                                    color: AppColors.primary,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                      if (attachmentUrl != null && attachmentUrl.isNotEmpty) ...[
                                        const SizedBox(height: 6),
                                        Container(
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 8,
                                            vertical: 6,
                                          ),
                                          decoration: BoxDecoration(
                                            color: Colors.black.withValues(alpha: 0.06),
                                            borderRadius: BorderRadius.circular(8),
                                          ),
                                          child: Row(
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              const Icon(Icons.attach_file, size: 14),
                                              const SizedBox(width: 6),
                                              Flexible(
                                                child: Text(
                                                  attachmentName == null || attachmentName.isEmpty
                                                      ? 'Attachment'
                                                      : attachmentName,
                                                  overflow: TextOverflow.ellipsis,
                                                  style: const TextStyle(fontSize: 11),
                                                ),
                                              ),
                                              const SizedBox(width: 6),
                                              InkWell(
                                                onTap: () async {
                                                  await _openUrl(
                                                    attachmentUrl,
                                                    error: 'Could not open attachment.',
                                                  );
                                                },
                                                child: const Text(
                                                  'Open',
                                                  style: TextStyle(
                                                    fontSize: 11,
                                                    fontWeight: FontWeight.w700,
                                                    color: AppColors.primary,
                                                  ),
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                      Align(
                                        alignment: Alignment.centerRight,
                                        child: _tick(m),
                                      ),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            if (isDriver) ...[
                              const SizedBox(width: 6),
                              _avatar(
                                _profile?['driver_profile_url'] as String?,
                                isDriver: true,
                              ),
                            ],
                          ],
                        ),
                      );
                    },
                  ),
              ],
            ),
          ),
          SafeArea(
            top: false,
            child: Container(
              padding: const EdgeInsets.fromLTRB(10, 8, 10, 8),
              color: Colors.white,
              child: Row(
                children: [
                  IconButton(
                    onPressed: _sending ? null : _pickAndSendVoiceNote,
                    icon: Icon(_recordingVoiceNote ? Icons.stop_circle_outlined : Icons.mic_none),
                    tooltip: _recordingVoiceNote ? 'Stop and send voice note' : 'Record voice note',
                  ),
                  IconButton(
                    onPressed: () {
                      FocusScope.of(context).unfocus();
                      setState(() => _showEmojiPicker = !_showEmojiPicker);
                    },
                    icon: const Icon(Icons.emoji_emotions_outlined),
                    tooltip: 'Emoji',
                  ),
                  IconButton(
                    onPressed: _sending ? null : _pickAndSendAttachment,
                    icon: const Icon(Icons.attach_file),
                    tooltip: 'Attach file',
                  ),
                  IconButton(
                    onPressed: _sending ? null : _sendCallInvite,
                    icon: const Icon(Icons.call),
                    tooltip: 'Send call invite',
                  ),
                  Expanded(
                    child: TextField(
                      controller: _controller,
                      minLines: 1,
                      maxLines: 4,
                      onTap: () {
                        if (_showEmojiPicker) {
                          setState(() => _showEmojiPicker = false);
                        }
                      },
                      decoration: const InputDecoration(
                        hintText: 'Type a message',
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  FloatingActionButton.small(
                    onPressed: _sending ? null : _send,
                    backgroundColor: AppColors.darkSlateGrey,
                    child: _sending
                        ? const SizedBox(
                            width: 14,
                            height: 14,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.send, color: Colors.white, size: 18),
                  ),
                ],
              ),
            ),
          ),
          if (_showEmojiPicker)
            SizedBox(
              height: 280,
              child: EmojiPicker(
                textEditingController: _controller,
                onEmojiSelected: (category, emoji) => _onEmojiSelected(emoji),
                config: const Config(
                  emojiViewConfig: EmojiViewConfig(columns: 8, emojiSizeMax: 28),
                  skinToneConfig: SkinToneConfig(enabled: true),
                  categoryViewConfig: CategoryViewConfig(),
                  bottomActionBarConfig: BottomActionBarConfig(enabled: true),
                  searchViewConfig: SearchViewConfig(),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _ChatPatternBackground extends StatelessWidget {
  const _ChatPatternBackground();

  @override
  Widget build(BuildContext context) {
    const icons = [
      Icons.local_shipping_outlined,
      Icons.location_on_outlined,
      Icons.description_outlined,
      Icons.alt_route_outlined,
      Icons.settings_outlined,
    ];

    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        final height = constraints.maxHeight;
        final items = <Widget>[];
        var i = 0;

        for (double y = 8; y < height; y += 56) {
          for (double x = 8; x < width; x += 56) {
            final icon = icons[i % icons.length];
            items.add(
              Positioned(
                left: x,
                top: y + ((i % 2 == 0) ? 0 : 10),
                child: Icon(
                  icon,
                  size: 14,
                  color: const Color(0xFFB6C0CC).withValues(alpha: 0.28),
                ),
              ),
            );
            i++;
          }
        }

        return Container(
          color: const Color(0xFFEFEAE2),
          child: Stack(children: items),
        );
      },
    );
  }
}
