<x-mail::message>
# Welcome to AI Job Recommendation! 🎉

Hello **{{ $user->name }}**,

Thank you for registering with AI Job Recommendation! To complete your registration and start finding your dream job, please verify your email address by clicking the button below:

<x-mail::button :url="$verificationUrl" color="primary">
Verify Email Address
</x-mail::button>

If you did not create an account, no further action is required.

If the button doesn't work, you can also copy and paste this link into your browser:
<p><a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a></p>

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
