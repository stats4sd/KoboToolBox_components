<html>
<div class="card">
<h2 text-center>TEAM INVITATION</h2>
<p>Dear {{ $name }},</p>
<p>You are invited to be part of COFFEE Platform.</p>
<p>Please follow the link below and use the following details to log in. Once you have logged in, you will be asked to change your password. Please make sure you do this immediately to ensure your account remains secure.</p>
<p> <a href= "{{ config('app.url').'/admin/edit-account-info' }}">{{ config('app.url').'/admin/edit-account-info' }}</a><br/>
<b>Email: </b>{{ $email }}<br/>
<b>Password: </b>{{ $password }}</p>
<p>If you have been sent this email in error, please contact us at {{ config('mail.admin_email') }} and we will ensure your details are removed from the system.</p>
<br/>
<br/>
<p>Best Regards,<br/>
Stats4SD IT Support</p>

</div>
</html>