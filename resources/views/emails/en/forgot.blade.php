<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
</head>
<body style="font-size: 20px; text-align: center; margin: 0; position: relative;">
<div style="height: 10px; background-color: #FFAF41; margin-bottom: 20px;"></div>

<b>Reset Your Password</b>
<br>
<br>
You can reset your password by clicking on this link.
<a href="{{$user->reset_url}}">{{$user->reset_url}}</a>
<br>
<br>
<a href="{{$user->reset_url}}" style="background-color: #4caf50; color: #ffffff; border-radius: 50px; cursor: pointer; display: inline-block; font-size: 14px; font-weight: bold; margin: 0; padding: 0 30px; text-decoration: none; text-transform: capitalize; height: 50px; line-height: 50px;">
    Reset Your Password
</a>
</body>
</html>