@component('mail::message')
Salut!

Nous sommes heureux de vous accueillir comme tuteur dans notre plateforme, et de savoir que vous accompagnerez {{$data['name'] }} dans son processus d’apprentissage.
email de votre apprenant:{{$data['email']}}
{{-- @component('mail::button', ['url' => ''])
m'inscrire aussi
@endcomponent --}}

Merci,<br>
{{ config('app.name') }}
@endcomponent
