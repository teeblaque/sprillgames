<!DOCTYPE html>
<html>
<head>
    <title>Reset your password</title>
</head>
<body>
    <h1>Hi {{ $mailData['name'] }}</h1>
    <p>Click this <a href={{ $mailData['url'] }}>link</a> to reset your password</p>
  
    <p>This code will only be valid for the next 7 days. If the link does not work, you can regenerate a new password reset link.</p>
    {{-- <p>
        This email was sent to olivia@untitledui.com. If you'd rather not receive this kind of email, you can unsubscribe or manage your email preferences.
    </p> --}}
    
    <p>© 2077 Untitled UI, 100 Smith Street, Melbourne VIC 3000</p>
</body>
</html>