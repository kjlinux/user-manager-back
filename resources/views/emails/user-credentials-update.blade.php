<x-mail::message>
# Mise à jour de votre compte

Bonjour {{ $user->name }},

Vos identifiants ont été mis à jour avec succès.

<x-mail::panel>
**Nouveaux identifiants :**
- **Email :** {{ $user->email }}
- **Nouveau mot de passe :** {{ $password }}
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/login'">
Se connecter
</x-mail::button>

Merci,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
