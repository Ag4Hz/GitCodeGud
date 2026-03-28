<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<p>You have been invited to join <strong>{{ $organization->name }}</strong> on GitCodeGud.</p>

<p>
    <a href="{{ $acceptUrl }}">Accept Invitation</a>
    &nbsp;&nbsp;
    <a href="{{ $declineUrl }}">Decline Invitation</a>
</p>

<p>This invitation expires in 7 days.</p>
</body>
</html>
