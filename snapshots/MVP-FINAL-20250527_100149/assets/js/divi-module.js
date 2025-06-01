import React from 'react';
import ReactDOM from 'react-dom';
import { apiFetch } from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

// Composant principal du calendrier
const CalendrierRdv = ({ style, primaryColor, apiUrl, nonce }) => {
    const [loading, setLoading] = React.useState(true);
    const [error, setError] = React.useState(null);
    const [services, setServices] = React.useState([]);
    const [providers, setProviders] = React.useState([]);
    const [selectedService, setSelectedService] = React.useState(null);
    const [selectedProvider, setSelectedProvider] = React.useState(null);
    const [selectedDate, setSelectedDate] = React.useState(null);
    const [selectedTime, setSelectedTime] = React.useState(null);

    React.useEffect(() => {
        // Charger les données initiales
        loadInitialData();
    }, []);

    const loadInitialData = async () => {
        try {
            const [servicesData, providersData] = await Promise.all([
                apiFetch({
                    path: '/calendrier-rdv/v1/services',
                    method: 'GET',
                    nonce: nonce
                }),
                apiFetch({
                    path: '/calendrier-rdv/v1/providers',
                    method: 'GET',
                    nonce: nonce
                })
            ]);

            setServices(servicesData);
            setProviders(providersData);
            setLoading(false);
        } catch (err) {
            setError(__('Une erreur est survenue lors du chargement des données.', 'calendrier-rdv'));
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!selectedService || !selectedProvider || !selectedDate || !selectedTime) {
            setError(__('Veuillez remplir tous les champs obligatoires.', 'calendrier-rdv'));
            return;
        }

        try {
            const response = await apiFetch({
                path: '/calendrier-rdv/v1/appointments',
                method: 'POST',
                nonce: nonce,
                data: {
                    service_id: selectedService,
                    provider_id: selectedProvider,
                    date: selectedDate,
                    time: selectedTime
                }
            });

            if (response.success) {
                // Redirection ou message de succès
                window.location.href = response.redirect_url;
            } else {
                setError(response.message);
            }
        } catch (err) {
            setError(__('Une erreur est survenue lors de la réservation.', 'calendrier-rdv'));
        }
    };

    if (loading) {
        return (
            <div className="calendrier-rdv-loading">
                {__('Chargement...', 'calendrier-rdv')}
            </div>
        );
    }

    if (error) {
        return (
            <div className="calendrier-rdv-error">
                {error}
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} className={`calendrier-rdv-form style-${style}`}>
            <div className="calendrier-rdv-section">
                <label htmlFor="service">
                    {__('Service', 'calendrier-rdv')}
                </label>
                <select
                    id="service"
                    value={selectedService}
                    onChange={(e) => setSelectedService(e.target.value)}
                    required
                >
                    <option value="">{__('Sélectionnez un service', 'calendrier-rdv')}</option>
                    {services.map(service => (
                        <option key={service.id} value={service.id}>
                            {service.name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="calendrier-rdv-section">
                <label htmlFor="provider">
                    {__('Prestataire', 'calendrier-rdv')}
                </label>
                <select
                    id="provider"
                    value={selectedProvider}
                    onChange={(e) => setSelectedProvider(e.target.value)}
                    required
                >
                    <option value="">{__('Sélectionnez un prestataire', 'calendrier-rdv')}</option>
                    {providers.map(provider => (
                        <option key={provider.id} value={provider.id}>
                            {provider.name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="calendrier-rdv-section">
                <label htmlFor="date">
                    {__('Date', 'calendrier-rdv')}
                </label>
                <input
                    type="date"
                    id="date"
                    value={selectedDate}
                    onChange={(e) => setSelectedDate(e.target.value)}
                    required
                />
            </div>

            <div className="calendrier-rdv-section">
                <label htmlFor="time">
                    {__('Heure', 'calendrier-rdv')}
                </label>
                <input
                    type="time"
                    id="time"
                    value={selectedTime}
                    onChange={(e) => setSelectedTime(e.target.value)}
                    required
                />
            </div>

            <button type="submit" className="calendrier-rdv-submit">
                {__('Réserver', 'calendrier-rdv')}
            </button>
        </form>
    );
};

// Initialisation du composant React
const initCalendrierRdv = () => {
    document.querySelectorAll('.calendrier-rdv-react-root').forEach(container => {
        const { style, primaryColor } = container.dataset;
        ReactDOM.createRoot(container).render(
            <CalendrierRdv
                style={style}
                primaryColor={primaryColor}
                apiUrl={calendrierRdvConfig.ajax_url}
                nonce={calendrierRdvConfig.nonce}
            />
        );
    });
};

// Démarrer l'application lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', initCalendrierRdv);
