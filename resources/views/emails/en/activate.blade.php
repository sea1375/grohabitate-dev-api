<!DOCTYPE html>
<html>
<head>
    <title>Account Activation Required</title>
</head>
<body style="font-size: 20px; text-align: center; margin: 0; position: relative;">
<div style="height: 10px; background-color: #FFAF41; margin-bottom: 20px;"></div>
<br><br>
Hello <b>{{$user->username}}</b>
<br>
<br>
Please verify your email address to activate your account.
<br>
<br>
<a href="{{$user->confirmation_url}}" style="background-color: #4caf50; color: #ffffff; border-radius: 50px; cursor: pointer; display: inline-block; font-size: 14px; font-weight: bold; margin: 0; padding: 0 30px; text-decoration: none; text-transform: capitalize; height: 50px; line-height: 50px;">
    Activate Your Account
</a>
<br>
<br>
You can activate your account by clicking this URL.. <a href="{{$user->confirmation_url}}">{{$user->confirmation_url}}</a>
<br>
<br>
</body>
</html>