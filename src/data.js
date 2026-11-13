export const DEFAULT_CONFIG = {
  waNumber: '22900000000',
  phoneDisplay: '+229 00 00 00 00',
  address: 'Parakou, Quartier Zongo, Bénin',
  hours: 'Lun - Sam : 08h00 - 19h00',
};

const image = (id, width = 800) =>
  `https://images.unsplash.com/${id}?auto=format&fit=crop&w=${width}&q=80`;

export const DEFAULT_VEHICLES = [
  {
    id: 1,
    title: 'Toyota RAV4 Limited',
    brand: 'Toyota',
    price: 12500000,
    year: 2020,
    km: '45 000 km',
    fuel: 'Essence',
    transmission: 'Automatique',
    engine: '2.5L 4-Cylindres',
    status: 'available',
    image: image('photo-1552519507-da3b142c6e3d'),
    gallery: [
      image('photo-1552519507-da3b142c6e3d', 1200),
      image('photo-1542282088-72c9c27ed0cd', 1200),
      image('photo-1583121274602-3e2820c69888', 1200),
    ],
    description:
      "Toyota RAV4 en parfait état mécanique, boîte automatique, intérieur cuir, climatisation d'origine.",
  },
  {
    id: 2,
    title: 'Hyundai Tucson Sport',
    brand: 'Hyundai',
    price: 9800000,
    year: 2018,
    km: '62 000 km',
    fuel: 'Essence',
    transmission: 'Automatique',
    engine: '2.0L 4-Cylindres',
    status: 'available',
    image: image('photo-1542282088-72c9c27ed0cd'),
    gallery: [
      image('photo-1542282088-72c9c27ed0cd', 1200),
      image('photo-1552519507-da3b142c6e3d', 1200),
    ],
    description:
      "Hyundai Tucson d'occasion très propre, volant multifonction, caméra de recul.",
  },
  {
    id: 3,
    title: 'Mercedes-Benz Classe C 200',
    brand: 'Mercedes-Benz',
    price: 16000000,
    year: 2019,
    km: '50 000 km',
    fuel: 'Essence',
    transmission: 'Automatique',
    engine: '2.0L Turbo 4-Cylindres',
    status: 'reserved',
    image: image('photo-1617814076367-b759c7d7e738'),
    gallery: [
      image('photo-1617814076367-b759c7d7e738', 1200),
      image('photo-1583121274602-3e2820c69888', 1200),
    ],
    description:
      'Berline de luxe Mercedes Classe C, confort exceptionnel, full options.',
  },
  {
    id: 4,
    title: 'Kia Sportage EX',
    brand: 'Kia',
    price: 8500000,
    year: 2017,
    km: '78 000 km',
    fuel: 'Diesel',
    transmission: 'Manuelle',
    engine: '1.7L Diesel',
    status: 'sold',
    image: image('photo-1583121274602-3e2820c69888'),
    gallery: [image('photo-1583121274602-3e2820c69888', 1200)],
    description:
      'SUV compact économique, faible consommation Diesel, parfaitement entretenu.',
  },
];

export const NAV_ITEMS = [
  { id: 'home', label: 'Accueil' },
  { id: 'vehicles', label: 'Nos Véhicules' },
  { id: 'services', label: 'Nos Services' },
  { id: 'about', label: 'À Propos' },
  { id: 'contact', label: 'Contact' },
];

// Mode démo (barre de simulation + véhicules fictifs si l'API est absente).
// - en développement (npm run dev) : actif, sauf VITE_DEMO_MODE=false
// - en production (npm run build)   : INACTIF, sauf VITE_DEMO_MODE=true
export const DEMO_MODE = import.meta.env.DEV
  ? import.meta.env.VITE_DEMO_MODE !== 'false'
  : import.meta.env.VITE_DEMO_MODE === 'true';

// Adresses de chaque page (routage par # : fonctionne sans configuration serveur)
export const ROUTE_SLUGS = {
  home: '',
  vehicles: 'vehicules',
  services: 'services',
  about: 'a-propos',
  contact: 'contact',
};

export const hrefFor = (page, vehicleId) => {
  if (page === 'vehicle-detail' && vehicleId != null) return `#/vehicules/${vehicleId}`;
  return `#/${ROUTE_SLUGS[page] ?? ''}`;
};

export const parseHash = (hash = window.location.hash) => {
  const [slug = '', id = ''] = hash.replace(/^#\/?/, '').replace(/\/+$/, '').split('/');
  if (slug === 'vehicules' && id) return { page: 'vehicle-detail', vehicleId: id };
  const page = Object.keys(ROUTE_SLUGS).find((key) => ROUTE_SLUGS[key] === slug && key !== 'home');
  return { page: page || 'home', vehicleId: null };
};

export const formatPrice = (price) =>
  `${new Intl.NumberFormat('fr-FR').format(Number(price) || 0)} FCFA`;

export const normalizeVehicle = (vehicle) => {
  const images =
    Array.isArray(vehicle.images) && vehicle.images.length
      ? vehicle.images
      : Array.isArray(vehicle.gallery) && vehicle.gallery.length
        ? vehicle.gallery
        : vehicle.image
          ? [vehicle.image]
          : [];

  return { ...vehicle, image: images[0] || '', gallery: images };
};

export const whatsappUrl = (number, message) =>
  `https://wa.me/${number}?text=${encodeURIComponent(message)}`;
