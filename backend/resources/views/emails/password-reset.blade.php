<x-mail::message>
# Reset Your Password 🔐

Hello **{{ $user->name }}**,

You recently requested to reset your password for your AI Job Recommendation account. Click the button below to reset your password:

<x-mail::button :url="$resetUrl" color="primary">
Reset Password
</x-mail::button>

This password reset link will expire in 60 minutes.

If you did not request a password reset, please ignore this email. Your password will remain unchanged.

If the button doesn't work, you can also copy and paste this link into your browser:
<p><a href="{{ $resetUrl }}">{{ $resetUrl }}</a></p>

For security reasons, please reset your password as soon as possible.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
