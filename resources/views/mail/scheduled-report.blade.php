<x-mail::message>
# Scheduled Report: {{ $reportName }}

Your {{ strtolower($frequency) }} scheduled report is attached to this email.

**Report:** {{ $reportName }}
**Frequency:** {{ $frequency }}
**Generated:** {{ now()->format('d M Y H:i') }}

Thanks,<br>
Tanaki Hub
</x-mail::message>
