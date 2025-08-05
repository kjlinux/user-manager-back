# Mise à jour de votre compte

Bonjour {{ $user->name }},

Vos identifiants ont été mis à jour avec succès.

**Nouveaux identifiants :**
- Email : {{ $user->email }}
- Nouveau mot de passe : {{ $password }}

[Se connecter]({{ config('app.url') }}/login)

---
Équipe {{ config('app.name') }}