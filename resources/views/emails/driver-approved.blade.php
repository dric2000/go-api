<x-mail::message>
# Félicitations {{ $user->name }} ! 🎉

Bonne nouvelle : votre profil conducteur vient d'être **validé**.

Vous pouvez désormais proposer des trajets et commencer à covoiturer avec la communauté Go.

<x-mail::button :url="url('/profile')">
Voir mon profil
</x-mail::button>

À très vite sur la route,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>