<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Holidaze</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans bg-gray-50">

  <!-- Header -->
  <header class="flex items-center justify-between px-6 py-4 bg-white shadow">
    <div class="text-2xl font-bold text-blue-600">Holidaze</div>
    <nav class="flex space-x-6 text-gray-700">
      <a href="#" class="hover:text-blue-600">Louer</a>
      <a href="#" class="hover:text-blue-600">Cartes</a>
    </nav>
    <div class="flex space-x-4 items-center">
      <button><i class="fa fa-heart"></i></button>
      <button><i class="fa fa-user"></i></button>
    </div>
  </header>

  <!-- Hero -->
  <section class="relative bg-cover bg-center h-[400px]" style="background-image: url('https://source.unsplash.com/1600x600/?apartment');">
    <div class="absolute inset-0 bg-black bg-opacity-40 flex flex-col justify-center items-center text-center text-white px-4">
      <h1 class="text-4xl font-bold mb-4">Des locations meublées pour des séjours d'exception</h1>
      <p class="mb-6">Visitez la France, la culture et la gastronomie, en toute simplicité.</p>
      
      <!-- Search Bar -->
      <div class="flex bg-white rounded-lg shadow-lg overflow-hidden w-full max-w-3xl">
        <input type="text" placeholder="Où allez-vous ?" class="px-4 py-3 flex-1 border-r outline-none">
        <input type="date" class="px-4 py-3 border-r outline-none">
        <input type="number" placeholder="Invités" class="px-4 py-3 border-r outline-none">
        <button class="bg-blue-600 text-white px-6">Rechercher</button>
      </div>
    </div>
  </section>

  <!-- Villes Populaires -->
  <section class="px-6 py-12">
    <h2 class="text-2xl font-semibold mb-6">Villes Populaires</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
      <div class="rounded-xl overflow-hidden shadow">
        <img src="https://source.unsplash.com/400x250/?paris" alt="Paris">
        <div class="p-4 font-medium">Paris</div>
      </div>
      <div class="rounded-xl overflow-hidden shadow">
        <img src="https://source.unsplash.com/400x250/?marseille" alt="Marseille">
        <div class="p-4 font-medium">Marseille</div>
      </div>
      <div class="rounded-xl overflow-hidden shadow">
        <img src="https://source.unsplash.com/400x250/?lyon" alt="Lyon">
        <div class="p-4 font-medium">Lyon</div>
      </div>
      <div class="rounded-xl overflow-hidden shadow">
        <img src="https://source.unsplash.com/400x250/?bordeaux" alt="Bordeaux">
        <div class="p-4 font-medium">Bordeaux</div>
      </div>
    </div>
  </section>

  <!-- Sélections pour vous -->
  <section class="px-6 py-12">
    <h2 class="text-2xl font-semibold mb-6">Sélections pour vous</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="rounded-xl overflow-hidden shadow bg-white">
        <img src="https://source.unsplash.com/400x250/?house" alt="">
        <div class="p-4">
          <h3 class="font-semibold">Maison en pierre avec vue mer</h3>
          <p class="text-gray-600">€180 / nuit</p>
          <p class="text-yellow-500">⭐ 4.8</p>
        </div>
      </div>
      <div class="rounded-xl overflow-hidden shadow bg-white">
        <img src="https://source.unsplash.com/400x250/?apartment" alt="">
        <div class="p-4">
          <h3 class="font-semibold">Appartement haussmannien chic</h3>
          <p class="text-gray-600">€200 / nuit</p>
          <p class="text-yellow-500">⭐ 4.6</p>
        </div>
      </div>
      <div class="rounded-xl overflow-hidden shadow bg-white">
        <img src="https://source.unsplash.com/400x250/?loft" alt="">
        <div class="p-4">
          <h3 class="font-semibold">Loft moderne avec terrasse</h3>
          <p class="text-gray-600">€150 / nuit</p>
          <p class="text-yellow-500">⭐ 4.7</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-100 py-10 px-6 text-gray-600">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
      <div>
        <h4 class="font-semibold mb-2">Holidaze</h4>
        <p>Locations meublées et séjours inoubliables partout en France.</p>
      </div>
      <div>
        <h4 class="font-semibold mb-2">Découvrir</h4>
        <ul>
          <li><a href="#" class="hover:underline">Destinations populaires</a></li>
          <li><a href="#" class="hover:underline">Expériences uniques</a></li>
        </ul>
      </div>
      <div>
        <h4 class="font-semibold mb-2">Assistance</h4>
        <ul>
          <li><a href="#" class="hover:underline">Centre d’aide</a></li>
          <li><a href="#" class="hover:underline">Conditions</a></li>
        </ul>
      </div>
    </div>
    <div class="text-center mt-8 text-sm">© 2025 Holidaze. Tous droits réservés.</div>
  </footer>

</body>
</html>
