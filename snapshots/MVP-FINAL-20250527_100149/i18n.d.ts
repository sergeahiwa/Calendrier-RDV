// Déclarations de types pour les fichiers de traduction
declare module '*.json' {
  const value: {
    [key: string]: string | Record<string, unknown>;
  };
  export default value;
}

// Extension de l'interface Window pour inclure les variables globales
declare interface Window {
  calendrierRdvVars: {
    ajaxUrl: string;
    pluginUrl: string;
    restUrl: string;
    nonce: string;
    locale: string;
    translations: {
      [key: string]: string;
    };
  };
}

// Déclaration des modules pour les imports de fichiers
declare module '*.png' {
  const value: string;
  export default value;
}

declare module '*.jpg' {
  const value: string;
  export default value;
}

declare module '*.svg' {
  const value: string;
  export default value;
}
