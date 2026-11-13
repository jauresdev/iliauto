import { useMemo, useState } from 'react';
import {
  ArrowLeft,
  ArrowRight,
  CalendarDays,
  Car,
  CarFront,
  Clock3,
  Cpu,
  Fuel,
  Gauge,
  MapPin,
  MessageCircle,
  MessageSquare,
  Phone,
  ShoppingBag,
  Star,
  Truck,
  Wrench,
} from 'lucide-react';
import { formatPrice, hrefFor, whatsappUrl } from './data.js';

const iconMap = {
  shopping: ShoppingBag,
  wrench: Wrench,
  cpu: Cpu,
  truck: Truck,
};

const serviceCards = [
  {
    icon: 'shopping',
    title: 'Vente Automobile',
    description:
      "Véhicules neufs et d'occasion rigoureusement contrôlés et documentés.",
    message: 'Bonjour IliAuto, je souhaite des infos sur la vente de véhicules.',
    action: 'En savoir plus',
    className: 'btn-outline',
  },
  {
    icon: 'wrench',
    title: 'Entretien & Vidange',
    description:
      'Maintenance régulière, vidanges et révisions complètes pour prolonger la durée de vie de votre véhicule.',
    message:
      "Bonjour IliAuto, je souhaite obtenir des informations sur vos services d'entretien automobile.",
    action: 'Réserver un entretien',
    className: 'btn-outline',
  },
  {
    icon: 'cpu',
    title: 'Diagnostic Électronique',
    description:
      'Analyse informatisée et détection précise de toutes les pannes mécaniques et électroniques.',
    message:
      'Bonjour IliAuto, je souhaite prendre rendez-vous pour un diagnostic électronique.',
    action: 'Demander un diagnostic',
    className: 'btn-outline',
  },
  {
    icon: 'truck',
    title: 'Dépannage Rapide',
    description:
      'Intervention rapide sur Parakou et ses environs pour vous dépanner en toute circonstance.',
    message: "Bonjour IliAuto, j'ai besoin d'une assistance automobile.",
    action: "Besoin d'assistance",
    className: 'btn-whatsapp',
  },
];

const detailedServices = [
  {
    image:
      'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=800&q=80',
    alt: 'Vente Automobile',
    title: '1. Vente & Conseils Automobiles',
    description:
      'Nous vous proposons un choix rigoureux de véhicules d’occasion certifiés et neufs. Chaque voiture mise en vente subit une inspection en 50 points de contrôle.',
    message:
      "Bonjour IliAuto, je souhaite des conseils pour l'achat d'un véhicule.",
    action: 'En discuter sur WhatsApp',
    reverse: false,
  },
  {
    image:
      'https://images.unsplash.com/photo-1486006920555-c77dce18193b?auto=format&fit=crop&w=800&q=80',
    alt: 'Entretien et Vidange',
    title: '2. Entretien & Vidange Régulière',
    description:
      "Pour préserver les performances de votre moteur, nous effectuons le changement d'huile, le remplacement des filtres (air, huile, carburant) et les contrôles des liquides.",
    message:
      "Bonjour IliAuto, je souhaite obtenir des informations sur vos services d'entretien.",
    action: 'Prendre Rendez-vous',
    reverse: true,
  },
  {
    image:
      'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?auto=format&fit=crop&w=800&q=80',
    alt: 'Diagnostic Électronique',
    title: '3. Diagnostic Électronique & Valise',
    description:
      'Équipés de scanners automobiles multi-marques, nous identifions avec exactitude les voyants moteur, dysfonctionnements ABS, airbag et calculateurs.',
    message:
      'Bonjour IliAuto, je souhaite faire un diagnostic électronique.',
    action: 'Demander un Diagnostic',
    reverse: false,
  },
  {
    image:
      'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&w=800&q=80',
    alt: 'Dépannage Rapide',
    title: '4. Dépannage Rapide',
    description:
      'En panne sur la route ? Notre équipe intervient rapidement à Parakou et dans ses environs pour diagnostiquer et remettre votre véhicule en état de rouler.',
    message:
      "Bonjour IliAuto, j'ai besoin d'un dépannage. Mon véhicule est en panne.",
    action: 'Demander un Dépannage',
    reverse: true,
  },
];

const reviews = [
  {
    text: "J'ai acheté mon SUV chez IliAuto. Service impeccable, véhicule en parfait état et papiers en règle. Je recommande !",
    author: 'Koffi A.',
  },
  {
    text: 'Diagnostic et vidange faits en moins de 2h. Une équipe sérieuse et honnête qui prend le temps d’expliquer.',
    author: 'Moussa S.',
  },
  {
    text: 'Service de dépannage réactif. Ils m’ont débloqué un problème d’allumage sur la route rapidement.',
    author: 'Chantal B.',
  },
];

function SectionHeader({ tag, title, subtitle }) {
  return (
    <div className="section-header">
      <span className="section-tag">{tag}</span>
      <h2 className="section-title">{title}</h2>
      {subtitle && <p className="section-subtitle">{subtitle}</p>}
    </div>
  );
}

function WhatsAppLink({ number, message, children, className = 'btn btn-primary', style }) {
  return (
    <a
      href={whatsappUrl(number, message)}
      target="_blank"
      rel="noopener noreferrer"
      className={className}
      style={style}
    >
      {children}
    </a>
  );
}

// Message affiché à la place des véhicules : chargement, panne ou catalogue vide
function CatalogMessage({ status, config }) {
  if (status === 'loading') {
    return (
      <div className="empty-state" style={{ gridColumn: '1 / -1' }} role="status">
        <p>Chargement des véhicules…</p>
      </div>
    );
  }
  if (status === 'error') {
    return (
      <div className="empty-state" style={{ gridColumn: '1 / -1' }} role="status">
        <CarFront />
        <h3>Le catalogue est momentanément indisponible</h3>
        <p style={{ marginBottom: 20 }}>Réessayez dans quelques instants, ou contactez-nous directement.</p>
        <WhatsAppLink
          number={config.waNumber}
          message="Bonjour IliAuto, je souhaite connaître vos véhicules disponibles."
          className="btn btn-whatsapp"
        >
          <MessageSquare size={18} /> Voir les véhicules sur WhatsApp
        </WhatsAppLink>
      </div>
    );
  }
  return (
    <div className="empty-state" style={{ gridColumn: '1 / -1' }}>
      <CarFront />
      <h3>Aucun véhicule en vente pour le moment</h3>
      <p>Revenez bientôt ou contactez-nous pour connaître nos prochains arrivages.</p>
    </div>
  );
}

function VehicleCard({ vehicle, onOpen }) {
  const status = {
    available: { label: 'Disponible', className: 'badge-available' },
    reserved: { label: 'Réservé', className: 'badge-reserved' },
    sold: { label: 'Vendu', className: 'badge-sold' },
  }[vehicle.status] || { label: 'Disponible', className: 'badge-available' };

  return (
    <article className="vehicle-card">
      <div className="vehicle-img-wrapper">
        <img
          src={vehicle.image}
          alt={vehicle.title}
          loading="lazy"
          onError={(event) => {
            event.currentTarget.style.opacity = '0';
          }}
        />
        <span className={`badge ${status.className} vehicle-badge-pos`}>
          {status.label}
        </span>
        <div className="vehicle-price-tag">{formatPrice(vehicle.price)}</div>
      </div>
      <div className="vehicle-body">
        <h3 className="vehicle-title">{vehicle.title}</h3>
        <div className="vehicle-specs">
          <div className="spec-item"><CalendarDays size={16} /> {vehicle.year}</div>
          <div className="spec-item"><Gauge size={16} /> {vehicle.km || '—'}</div>
          <div className="spec-item"><Fuel size={16} /> {vehicle.fuel}</div>
          <div className="spec-item"><MapPin size={16} /> Parakou</div>
        </div>
        <div className="vehicle-footer">
          <button
            type="button"
            className="btn btn-primary"
            style={{ flex: 1 }}
            onClick={() => onOpen(vehicle.id)}
          >
            Voir détails
          </button>
        </div>
      </div>
    </article>
  );
}

export function HomePage({ vehicles, status, onNavigate, onOpenVehicle, config }) {
  return (
    <main>
      <section className="hero">
        <div className="container">
          <div className="hero-content">
            <span className="section-tag">Garage Automobile Spécialisé</span>
            <h1 className="hero-title">Votre partenaire automobile à <span>Parakou</span></h1>
            <p className="hero-subtitle">
              Vente, entretien, diagnostic et réparation mécanique : IliAuto vous accompagne pour tous vos besoins automobiles avec précision et transparence.
            </p>
            <div className="hero-btns">
              <button type="button" className="btn btn-primary" onClick={() => onNavigate('vehicles')}>
                <Car size={18} /> Découvrir nos véhicules
              </button>
              <button type="button" className="btn btn-outline" onClick={() => onNavigate('contact')}>
                <Phone size={18} /> Contacter IliAuto
              </button>
            </div>
          </div>
        </div>
      </section>

      <section className="stats-section">
        <div className="container">
          <div className="stats-grid">
            {[
              ['45+', 'Véhicules Disponibles'],
              ['1 200+', 'Clients Satisfaits'],
              ['4', 'Services Spécialisés'],
              ['10+', "Années d'Expérience"],
            ].map(([number, label]) => (
              <div key={label}>
                <div className="stat-number">{number}</div>
                <div className="stat-label">{label}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section-padding">
        <div className="container">
          <SectionHeader
            tag="Sélection Exclusive"
            title="Véhicules en Vedette"
            subtitle="Découvrez nos meilleures opportunités d'automobiles révisées et garanties à Parakou."
          />
          <div className="vehicles-grid">
            {vehicles.length > 0 ? vehicles.slice(0, 3).map((vehicle) => (
              <VehicleCard key={vehicle.id} vehicle={vehicle} onOpen={onOpenVehicle} />
            )) : <CatalogMessage status={status} config={config} />}
          </div>
          <div style={{ textAlign: 'center', marginTop: 40 }}>
            <button type="button" className="btn btn-outline" onClick={() => onNavigate('vehicles')}>
              Voir tous nos véhicules <ArrowRight size={18} />
            </button>
          </div>
        </div>
      </section>

      <section className="section-padding" style={{ background: 'var(--bg-secondary)' }}>
        <div className="container">
          <SectionHeader
            tag="Notre Expertise"
            title="Nos Prestations"
            subtitle="Un service complet assuré par des professionnels de l'automobile."
          />
          <div className="services-grid">
            {serviceCards.map((service) => {
              const Icon = iconMap[service.icon];
              return (
                <article className="service-card" key={service.title}>
                  <div className="service-icon"><Icon /></div>
                  <h3 className="service-title">{service.title}</h3>
                  <p className="service-desc">{service.description}</p>
                  <WhatsAppLink
                    number={config.waNumber}
                    message={service.message}
                    className={`btn ${service.className}`}
                    style={{ width: '100%' }}
                  >
                    {service.action}
                  </WhatsAppLink>
                </article>
              );
            })}
          </div>
        </div>
      </section>

      <section className="section-padding">
        <div className="container">
          <SectionHeader tag="Engagements" title="Pourquoi Choisir IliAuto ?" />
          <div className="features-grid">
            {[
              ['Accompagnement', 'Conseils sur-mesure adaptés à votre budget et à vos besoins réels.'],
              ['Transparence', 'Historique des véhicules et devis clairs avant toute intervention.'],
              ['Proximité', 'Implantation au cœur de Parakou pour un accès facile et rapide.'],
              ['Disponibilité', 'Équipe réactive par téléphone et WhatsApp du Lundi au Samedi.'],
            ].map(([title, text]) => (
              <div className="feature-box" key={title}>
                <h4>{title}</h4>
                <p>{text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section-padding" style={{ background: 'var(--bg-secondary)' }}>
        <div className="container">
          <SectionHeader tag="Avis Clients" title="Ce que disent nos clients" />
          <div className="reviews-grid">
            {reviews.map((review) => (
              <article className="review-card" key={review.author}>
                <div className="stars" aria-label="5 étoiles">
                  {Array.from({ length: 5 }, (_, index) => <Star key={index} />)}
                </div>
                <p className="review-text">"{review.text}"</p>
                <div className="review-author">{review.author}</div>
                <div className="review-location">Parakou</div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="section-padding">
        <div className="container">
          <div className="cta-banner">
            <h2 className="cta-title">Vous recherchez un véhicule ou avez besoin d'un service automobile ?</h2>
            <p className="cta-subtitle">
              Contactez directement notre équipe commerciale et technique sur WhatsApp pour une réponse immédiate.
            </p>
            <WhatsAppLink
              number={config.waNumber}
              message="Bonjour IliAuto, je souhaite obtenir des renseignements sur vos services."
              className="btn btn-whatsapp btn-lg"
            >
              <MessageSquare size={18} /> Discutons sur WhatsApp
            </WhatsAppLink>
          </div>
        </div>
      </section>
    </main>
  );
}

export function VehiclesPage({ vehicles, status, config, onOpenVehicle }) {
  const [search, setSearch] = useState('');
  const [brand, setBrand] = useState('');
  const [fuel, setFuel] = useState('');
  const [maxPrice, setMaxPrice] = useState('');
  const [sort, setSort] = useState('recent');
  const brands = useMemo(
    () => [...new Set(vehicles.map((vehicle) => vehicle.brand).filter(Boolean))].sort(),
    [vehicles],
  );
  const filteredVehicles = useMemo(() => {
    const query = search.trim().toLocaleLowerCase('fr');
    const result = vehicles.filter((vehicle) =>
      (!query || `${vehicle.title} ${vehicle.brand}`.toLocaleLowerCase('fr').includes(query))
      && (!brand || vehicle.brand === brand)
      && (!fuel || vehicle.fuel === fuel)
      && (!maxPrice || Number(vehicle.price) <= Number(maxPrice)),
    );
    if (sort === 'price-asc') result.sort((a, b) => a.price - b.price);
    if (sort === 'price-desc') result.sort((a, b) => b.price - a.price);
    return result;
  }, [vehicles, search, brand, fuel, maxPrice, sort]);

  return (
    <main>
      <section className="section-padding">
        <div className="container">
          <SectionHeader
            tag="Catalogue Complet"
            title="Nos Véhicules Disponibles"
            subtitle="Filtrez et recherchez le véhicule idéal adapté à vos critères."
          />
          <div className="filters-panel">
            <div className="filters-grid">
              <div className="filter-group">
                <label htmlFor="filter-search">Recherche</label>
                <input id="filter-search" className="form-control" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Marque, modèle..." />
              </div>
              <div className="filter-group">
                <label htmlFor="filter-brand">Marque</label>
                <select id="filter-brand" className="form-control" value={brand} onChange={(event) => setBrand(event.target.value)}>
                  <option value="">Toutes les marques</option>
                  {brands.map((name) => <option key={name} value={name}>{name}</option>)}
                </select>
              </div>
              <div className="filter-group">
                <label htmlFor="filter-fuel">Carburant</label>
                <select id="filter-fuel" className="form-control" value={fuel} onChange={(event) => setFuel(event.target.value)}>
                  <option value="">Tous carburants</option>
                  {[...new Set(vehicles.map((vehicle) => vehicle.fuel).filter(Boolean))].sort().map((name) => <option key={name} value={name}>{name}</option>)}
                </select>
              </div>
              <div className="filter-group">
                <label htmlFor="filter-price">Prix Maximum</label>
                <select id="filter-price" className="form-control" value={maxPrice} onChange={(event) => setMaxPrice(event.target.value)}>
                  <option value="">Tous les prix</option>
                  <option value="5000000">Jusqu'à 5 000 000 FCFA</option>
                  <option value="10000000">Jusqu'à 10 000 000 FCFA</option>
                  <option value="20000000">Jusqu'à 20 000 000 FCFA</option>
                </select>
              </div>
              <div className="filter-group">
                <label htmlFor="filter-sort">Trier par</label>
                <select id="filter-sort" className="form-control" value={sort} onChange={(event) => setSort(event.target.value)}>
                  <option value="recent">Plus récents</option>
                  <option value="price-asc">Prix : Croissant</option>
                  <option value="price-desc">Prix : Décroissant</option>
                </select>
              </div>
            </div>
          </div>
          <div className="vehicles-grid">
            {vehicles.length === 0 ? (
              <CatalogMessage status={status} config={config} />
            ) : filteredVehicles.length > 0 ? filteredVehicles.map((vehicle) => (
              <VehicleCard key={vehicle.id} vehicle={vehicle} onOpen={onOpenVehicle} />
            )) : (
              <div className="empty-state" style={{ gridColumn: '1 / -1' }}>
                <CarFront />
                <h3>Aucun véhicule ne correspond à vos critères</h3>
                <p>Essayez de réinitialiser vos filtres de recherche.</p>
              </div>
            )}
          </div>
        </div>
      </section>
    </main>
  );
}

export function VehicleDetailPage({ vehicle, status, config }) {
  const [selectedImage, setSelectedImage] = useState(0);

  if (!vehicle) {
    return (
      <main>
        <section className="section-padding">
          <div className="container">
            <div className="empty-state" role="status">
              {status === 'loading' ? (
                <p>Chargement du véhicule…</p>
              ) : (
                <>
                  <CarFront />
                  <h3>Ce véhicule n'est plus disponible</h3>
                  <p style={{ marginBottom: 20 }}>Il a peut-être été vendu ou retiré du catalogue.</p>
                  <a className="btn btn-primary" href={hrefFor('vehicles')}>
                    <ArrowLeft size={18} /> Voir tous les véhicules
                  </a>
                </>
              )}
            </div>
          </div>
        </section>
      </main>
    );
  }

  const gallery = vehicle.gallery?.length ? vehicle.gallery : [vehicle.image].filter(Boolean);
  const badge = {
    available: { label: 'Disponible', className: 'badge-available' },
    reserved: { label: 'Réservé', className: 'badge-reserved' },
    sold: { label: 'Vendu', className: 'badge-sold' },
  }[vehicle.status] || { label: 'Disponible', className: 'badge-available' };
  const message = `Bonjour IliAuto, je suis intéressé par le véhicule ${vehicle.title} (${vehicle.year}) affiché à ${formatPrice(vehicle.price)}. Pouvez-vous me donner plus d'informations ?`;

  return (
    <main>
      <section className="section-padding">
        <div className="container">
          <a className="btn btn-outline" style={{ marginBottom: 25 }} href={hrefFor('vehicles')}>
            <ArrowLeft size={18} /> Retour au catalogue
          </a>
          <div className="vehicle-details-grid">
            <div>
              <div className="gallery-main">
                {gallery[selectedImage] && (
                  <img
                    src={gallery[selectedImage]}
                    alt={`${vehicle.title} - photo ${selectedImage + 1}`}
                    onError={(event) => {
                      event.currentTarget.style.opacity = '0';
                    }}
                  />
                )}
              </div>
              <div className="gallery-thumbs">
                {gallery.map((src, index) => (
                  <button
                    type="button"
                    className={`thumb ${index === selectedImage ? 'active' : ''}`}
                    key={`${src}-${index}`}
                    onClick={() => setSelectedImage(index)}
                    aria-label={`Afficher la photo ${index + 1}`}
                  >
                    <img src={src} alt="" />
                  </button>
                ))}
              </div>
              <div style={{ marginTop: 40 }}>
                <h3 style={{ fontSize: '1.4rem', marginBottom: 15 }}>Description du véhicule</h3>
                <p style={{ color: 'var(--text-muted)', lineHeight: 1.7 }}>{vehicle.description || ''}</p>
              </div>
            </div>
            <div>
              <div className="details-sidebar">
                <span className={`badge ${badge.className}`}>{badge.label}</span>
                <h1 style={{ fontSize: '1.8rem', marginTop: 10 }}>{vehicle.title}</h1>
                <div className="details-price">{formatPrice(vehicle.price)}</div>
                <ul className="tech-specs-list">
                  <li><span>Année</span><strong>{vehicle.year}</strong></li>
                  <li><span>Kilométrage</span><strong>{vehicle.km || '—'}</strong></li>
                  <li><span>Carburant</span><strong>{vehicle.fuel}</strong></li>
                  <li><span>Transmission</span><strong>{vehicle.transmission || '—'}</strong></li>
                  <li><span>Motorisation</span><strong>{vehicle.engine || '—'}</strong></li>
                  <li><span>Localisation</span><strong>Parakou, Bénin</strong></li>
                </ul>
                <WhatsAppLink
                  number={config.waNumber}
                  message={message}
                  className="btn btn-whatsapp"
                  style={{ width: '100%', padding: 14 }}
                >
                  <MessageSquare size={18} /> Je suis intéressé par ce véhicule
                </WhatsAppLink>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}

export function ServicesPage({ config }) {
  return (
    <main>
      <section className="section-padding">
        <div className="container">
          <SectionHeader
            tag="Prestations & Maintenance"
            title="Nos Services Automobiles"
            subtitle="Découvrez en détail l'ensemble de nos compétences au garage IliAuto à Parakou."
          />
          {detailedServices.map((service) => (
            <div className={`service-detail-row ${service.reverse ? 'reverse' : ''}`} key={service.title}>
              <div className="service-detail-img"><img src={service.image} alt={service.alt} loading="lazy" /></div>
              <div>
                <h3>{service.title}</h3>
                <p>{service.description}</p>
                <WhatsAppLink number={config.waNumber} message={service.message} className={`btn ${service.title.startsWith('4.') ? 'btn-whatsapp' : 'btn-primary'}`}>
                  <MessageSquare size={18} /> {service.action}
                </WhatsAppLink>
              </div>
            </div>
          ))}
        </div>
      </section>
    </main>
  );
}

export function AboutPage() {
  return (
    <main>
      <section className="section-padding">
        <div className="container">
          <SectionHeader
            tag="Notre Histoire"
            title="À Propos d'IliAuto"
            subtitle="Le spécialiste automobile de référence à Parakou."
          />
          <div className="service-detail-row">
            <div>
              <h3>Excellence & Proximité</h3>
              <p style={{ marginBottom: 15 }}>
                Fondé avec la passion de l'automobile, le garage <strong>IliAuto</strong> s'est imposé à Parakou comme un acteur incontournable pour l'achat, l'entretien et la réparation de véhicules toutes marques.
              </p>
              <p>
                Notre mission est d'offrir à nos clients de la région du Borgou une alternative de confiance, combinant rigueur technique et clarté tarifaire.
              </p>
            </div>
            <div className="service-detail-img">
              <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80" alt="Garage IliAuto" loading="lazy" />
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}

export function ContactPage({ config }) {
  // Aucun serveur de messagerie : le message est ouvert dans WhatsApp, prêt à être envoyé.
  function handleSubmit(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const text = `Bonjour IliAuto, je m'appelle ${String(form.get('name')).trim()} (tél. : ${String(form.get('phone')).trim()}).\n\n${String(form.get('message')).trim()}`;
    window.open(whatsappUrl(config.waNumber, text), '_blank', 'noopener,noreferrer');
    event.currentTarget.reset();
  }

  return (
    <main>
      <section className="section-padding">
        <div className="container">
          <SectionHeader
            tag="Localisation & Contact"
            title="Contactez le Garage IliAuto"
            subtitle="Nous sommes à votre écoute pour toute question ou demande de rendez-vous."
          />
          <div className="contact-grid">
            <div className="contact-cards-stack">
              <div className="contact-card-item">
                <div className="contact-card-icon"><MapPin /></div>
                <div>
                  <h4 style={{ marginBottom: 4 }}>Localisation</h4>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>{config.address}</p>
                </div>
              </div>
              <div className="contact-card-item">
                <div className="contact-card-icon"><Phone /></div>
                <div>
                  <h4 style={{ marginBottom: 4 }}>Téléphone & WhatsApp</h4>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>{config.phoneDisplay}</p>
                </div>
              </div>
              <div className="contact-card-item">
                <div className="contact-card-icon"><Clock3 /></div>
                <div>
                  <h4 style={{ marginBottom: 4 }}>Horaires d'Ouverture</h4>
                  <p style={{ color: 'var(--text-muted)', fontSize: '0.9rem' }}>{config.hours}</p>
                </div>
              </div>
            </div>
            <div className="contact-form-card">
              <h3 style={{ marginBottom: 20 }}>Envoyez-nous un message</h3>
              <form onSubmit={handleSubmit}>
                <div className="form-row">
                  <label className="form-label" htmlFor="contact-name">Nom complet</label>
                  <input id="contact-name" name="name" type="text" maxLength={80} className="form-control" placeholder="Votre nom" required />
                </div>
                <div className="form-row">
                  <label className="form-label" htmlFor="contact-phone">Téléphone</label>
                  <input id="contact-phone" name="phone" type="tel" maxLength={30} className="form-control" placeholder="Votre numéro" required />
                </div>
                <div className="form-row">
                  <label className="form-label" htmlFor="contact-message">Message</label>
                  <textarea id="contact-message" name="message" maxLength={1000} className="form-control" rows="4" placeholder="Comment pouvons-nous vous aider ?" required />
                </div>
                <button type="submit" className="btn btn-whatsapp" style={{ width: '100%' }}>
                  <MessageSquare size={18} /> Envoyer sur WhatsApp
                </button>
                <p className="form-hint" style={{ marginTop: 10, fontSize: '0.8rem', color: 'var(--text-muted)', textAlign: 'center' }}>
                  Votre message s'ouvre dans WhatsApp, prêt à être envoyé à notre équipe.
                </p>
              </form>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}

export function Footer({ config }) {
  const navigationLinks = [
    ['Accueil', 'home'],
    ['Nos Véhicules', 'vehicles'],
    ['Nos Services', 'services'],
    ['À Propos', 'about'],
    ['Contact', 'contact'],
  ];

  const adminUrl = typeof window !== 'undefined' && ['localhost', '127.0.0.1'].includes(window.location.hostname)
    ? 'http://127.0.0.1:8000/admin/'
    : '/admin/';

  return (
    <footer className="site-footer">
      <div className="container">
        <div className="footer-grid">
          <div>
            <a href={hrefFor('home')} className="logo footer-logo">
              Ili<span>Auto</span>
            </a>
            <p style={{ marginBottom: 15 }}>
              Garage automobile professionnel à Parakou. Vente, entretien, réparation et accompagnement personnalisé.
            </p>
          </div>
          <div>
            <h4 className="footer-title">Navigation</h4>
            <ul className="footer-links">
              {navigationLinks.map(([label, page]) => (
                <li key={page}>
                  <a href={hrefFor(page)}>{label}</a>
                </li>
              ))}
            </ul>
          </div>
          <div>
            <h4 className="footer-title">Coordonnées</h4>
            <ul className="contact-info-list">
              <li><MapPin size={18} /> {config.address}</li>
              <li><Phone size={18} /> {config.phoneDisplay}</li>
              <li><Clock3 size={18} /> {config.hours}</li>
            </ul>
          </div>
        </div>
        <div className="footer-bottom" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 16, flexWrap: 'wrap' }}>
          <span>&copy; {new Date().getFullYear()} IliAuto Parakou. Tous droits réservés.</span>
          <a
            href={adminUrl}
            className="btn btn-outline"
            style={{
              padding: '8px 12px',
              fontSize: '0.75rem',
              borderColor: 'rgba(255,255,255,0.2)',
              color: 'var(--text-primary)',
              background: 'transparent',
            }}
          >
            Espace Admin
          </a>
        </div>
      </div>
    </footer>
  );
}

export function FloatingWhatsApp({ config }) {
  return (
    <a
      href={whatsappUrl(config.waNumber, 'Bonjour IliAuto, je souhaite obtenir des renseignements.')}
      target="_blank"
      rel="noopener noreferrer"
      className="whatsapp-float"
      title="Contactez-nous sur WhatsApp"
      aria-label="Contactez-nous sur WhatsApp"
    >
      <MessageCircle size={32} />
    </a>
  );
}
