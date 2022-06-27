@component('mail::message')
# Hello, {{ $data['approver_name']  }}

{{ $data['message'] }}

To approve or reject this request, jus click <b>Form Link</b>
and enter this password <b>{{ $data['password']  }}</b> please make sure
to not share this password.

@component('mail::button', ['url' => $data['link'], 'color' => 'red'])
    Form Link
@endcomponent


This is auto generated email.
Fur further assistance, please send us an email at <b>sdmd@usep.edu.ph</b>


Thank you.


If you are not the intended recipient, please contact us at sdmd@usep.edu.ph
and ignore or delete this email.
@endcomponent
