<x-mail::message>
# You're invited to collaborate

You've been invited to join Tanaki Hub{{ $role ? " as a {$role}" : '' }}.

Click below to set your password and get started.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

@if ($expiresAt)
This invitation expires on {{ $expiresAt->format('d M Y') }}.
@endif

Thanks,<br>
Tanaki Hub
</x-mail::message>
