import { useCallback, useEffect, useMemo, useState } from 'react';
import { Menu, MessageSquare, X } from 'lucide-react';
import {
  AboutPage,
  ContactPage,
  FloatingWhatsApp,
  Footer,
  HomePage,
  ServicesPage,
  VehicleDetailPage,
  VehiclesPage,
} from './pages.jsx';
import {
  DEFAULT_CONFIG,
  DEFAULT_VEHICLES,
  DEMO_MODE,
  NAV_ITEMS,
  hrefFor,
  normalizeVehicle,
  parseHash,
  whatsappUrl,
} from './data.js';

const DEFAULT_TITLE = 'IliAuto - Garage & Concessionnaire Automobile à Parakou';

export default function App() {
  // La page affichée est lue dans l'adresse (#/vehicules/3) : le bouton Retour
  // du navigateur fonctionne et chaque véhicule a un lien partageable.
  const [route, setRoute] = useState(() => parseHash());
  const [vehicles, setVehicles] = useState([]);
  const [config, setConfig] = useState(DEFAULT_CONFIG);
  // 'loading' | 'ready' | 'error'
  const [catalogStatus, setCatalogStatus] = useState('loading');
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const { page } = route;

  const selectedVehicle = useMemo(
    () => vehicles.find((vehicle) => String(vehicle.id) === String(route.vehicleId)),
    [vehicles, route.vehicleId],
  );

  const navigate = useCallback((nextPage, vehicleId) => {
    const target = hrefFor(nextPage, vehicleId);
    if (window.location.hash === target) {
      window.scrollTo({ top: 0, behavior: 'instant' });
      setMobileMenuOpen(false);
    } else {
      window.location.hash = target;
    }
  }, []);

  useEffect(() => {
    const onHashChange = () => setRoute(parseHash());
    window.addEventListener('hashchange', onHashChange);
    return () => window.removeEventListener('hashchange', onHashChange);
  }, []);

  useEffect(() => {
    setMobileMenuOpen(false);
    window.scrollTo({ top: 0, behavior: 'instant' });
  }, [route.page, route.vehicleId]);

  useEffect(() => {
    const controller = new AbortController();

    async function loadSiteData() {
      try {
        const response = await fetch('/api/vehicles.php', {
          headers: { Accept: 'application/json' },
          signal: controller.signal,
        });
        if (!response.ok) {
          throw new Error(`L'API du catalogue a répondu avec le statut ${response.status}.`);
        }

        const data = await response.json();
        if (!data || typeof data !== 'object' || !Array.isArray(data.vehicles)) {
          throw new Error("La réponse de l'API du catalogue est invalide.");
        }
        setVehicles(data.vehicles.map(normalizeVehicle));
        if (data.settings && typeof data.settings === 'object') {
          setConfig((current) => ({
            ...current,
            ...Object.fromEntries(
              Object.keys(DEFAULT_CONFIG)
                .filter((key) => typeof data.settings[key] === 'string')
                .map((key) => [key, data.settings[key]]),
            ),
          }));
        }
        setCatalogStatus('ready');
      } catch (error) {
        if (error.name === 'AbortError') return;
        console.error('Impossible de charger le catalogue IliAuto.', error);
        // Les véhicules fictifs ne sont utilisés qu'en mode démo.
        if (DEMO_MODE) setVehicles(DEFAULT_VEHICLES.map(normalizeVehicle));
        setCatalogStatus('error');
      }
    }

    loadSiteData();
    return () => controller.abort();
  }, []);

  useEffect(() => {
    document.title = page === 'vehicle-detail' && selectedVehicle
      ? `${selectedVehicle.title} - IliAuto`
      : DEFAULT_TITLE;
  }, [page, selectedVehicle]);

  const openVehicle = useCallback((id) => navigate('vehicle-detail', id), [navigate]);

  function renderPage() {
    switch (page) {
      case 'vehicles':
        return (
          <VehiclesPage
            vehicles={vehicles}
            status={catalogStatus}
            config={config}
            onOpenVehicle={openVehicle}
          />
        );
      case 'vehicle-detail':
        return (
          <VehicleDetailPage
            key={route.vehicleId}
            vehicle={selectedVehicle}
            status={catalogStatus}
            config={config}
          />
        );
      case 'services':
        return <ServicesPage config={config} />;
      case 'about':
        return <AboutPage />;
      case 'contact':
        return <ContactPage config={config} />;
      default:
        return (
          <HomePage
            vehicles={vehicles}
            status={catalogStatus}
            config={config}
            onNavigate={navigate}
            onOpenVehicle={openVehicle}
          />
        );
    }
  }

  const demoApiNotice = DEMO_MODE && catalogStatus === 'error';

  return (
    <>
      <header className="site-header" id="public-header">
        <div className="container nav-container">
          <a href={hrefFor('home')} className="logo">
            Ili<span>Auto</span>
          </a>
          <nav aria-label="Navigation principale">
            <ul className={`nav-menu ${mobileMenuOpen ? 'active' : ''}`} id="nav-menu">
              {NAV_ITEMS.map((item) => (
                <li key={item.id}>
                  <a
                    href={hrefFor(item.id)}
                    className={`nav-link ${
                      (page === 'vehicle-detail' ? item.id === 'vehicles' : page === item.id)
                        ? 'active'
                        : ''
                    }`}
                    aria-current={page === item.id ? 'page' : undefined}
                  >
                    {item.label}
                  </a>
                </li>
              ))}
            </ul>
          </nav>
          <div style={{ display: 'flex', gap: 15, alignItems: 'center' }}>
            <a
              href={whatsappUrl(config.waNumber, 'Bonjour IliAuto, je souhaite obtenir des renseignements sur vos services.')}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-whatsapp"
              style={{ padding: '9px 18px', fontSize: '0.85rem' }}
            >
              <MessageSquare size={18} /> WhatsApp
            </a>
            <button
              type="button"
              className="hamburger"
              aria-label={mobileMenuOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
              aria-expanded={mobileMenuOpen}
              onClick={() => setMobileMenuOpen((open) => !open)}
            >
              {mobileMenuOpen ? <X /> : <Menu />}
            </button>
          </div>
        </div>
      </header>

      {demoApiNotice && (
        <div className="api-notice" role="status">
          Mode démo : l'API PHP est indisponible, les véhicules de démonstration sont affichés.
        </div>
      )}

      {renderPage()}
      <Footer config={config} />
      <FloatingWhatsApp config={config} />
    </>
  );
}
