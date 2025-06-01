import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';

const CalendrierRdv = ({ style, primaryColor, apiUrl, nonce }) => {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [services, setServices] = useState([]);
    const [providers, setProviders] = useState([]);
    const [selectedService, setSelectedService] = useState(null);
    const [selectedProvider, setSelectedProvider] = useState(null);
    const [selectedDate, setSelectedDate] = useState(null);
    const [selectedTime, setSelectedTime] = useState(null);

    useEffect(() => {
        // Vérifier si React est déjà chargé
        if (!window.React) {
            setError('React n\'est pas disponible');
            return;
        }

        // Charger les données initiales
        loadInitialData();
    }, []);

    const loadInitialData = async () => {
        try {
            const [servicesData, providersData] = await Promise.all([
                fetch(`${apiUrl}?rest_route=/calendrier-rdv/v1/services`, {
                    headers: { 'X-WP-Nonce': nonce }
                }),
                fetch(`${apiUrl}?rest_route=/calendrier-rdv/v1/providers`, {
                    headers: { 'X-WP-Nonce': nonce }
                })
            ]);

            const [servicesJson, providersJson] = await Promise.all([
                servicesData.json(),
                providersData.json()
            ]);

            setServices(servicesJson);
            setProviders(providersJson);
            setLoading(false);
        } catch (err) {
            setError('Une erreur est survenue lors du chargement des données');
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!selectedService || !selectedProvider || !selectedDate || !selectedTime) {
            setError('Veuillez remplir tous les champs obligatoires');
            return;
        }

        try {
            const response = await fetch(`${apiUrl}?rest_route=/calendrier-rdv/v1/appointments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify({
                    service_id: selectedService,
                    provider_id: selectedProvider,
                    date: selectedDate,
                    time: selectedTime
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Une erreur est survenue lors de la réservation');
            }

            // Redirection ou message de succès
            window.location.href = data.redirect_url || window.location.href;
        } catch (err) {
            setError(err.message);
        }
    };

    if (loading) {
        return (
            <div className="calendrier-rdv-loading">
                <p>Chargement en cours...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="calendrier-rdv-error">
                <p>{error}</p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} className={`calendrier-rdv-form style-${style}`}>
            <div className="calendrier-rdv-section">
                <label htmlFor="service">
                    Service
                </label>
                <select
                    id="service"
                    value={selectedService}
                    onChange={(e) => setSelectedService(e.target.value)}
                    required
                >
                    <option value="">Sélectionnez un service</option>
                    {services.map(service => (
                        <option key={service.id} value={service.id}>
                            {service.name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="calendrier-rdv-section">
                <label htmlFor="provider">
                    Prestataire
                </label>
                <select
                    id="provider"
                    value={selectedProvider}
                    onChange={(e) => setSelectedProvider(e.target.value)}
                    required
                >
                    <option value="">Sélectionnez un prestataire</option>
                    {providers.map(provider => (
                        <option key={provider.id} value={provider.id}>
                            {provider.name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="calendrier-rdv-section">
                <label htmlFor="date">
                    Date
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
                    Heure
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
                Réserver
            </button>
        </form>
    );
};

// Initialisation du composant React
const initCalendrierRdv = () => {
    // Vérifier si React est déjà chargé
    if (!window.React) {
        console.error('React n\'est pas disponible');
        return;
    }

    document.querySelectorAll('.calendrier-rdv-react-root').forEach(container => {
        const { style, primaryColor } = container.dataset;
        const root = createRoot(container);
        root.render(
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

export default CalendrierRdv;
