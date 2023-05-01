import React from 'react';
import { createRoot } from 'react-dom/client';
import SettingsReading from './settings-reading';

const container = document.getElementById( 'app-reading' );
const root = createRoot( container );
root.render(
	<React.StrictMode>
		<SettingsReading />
	</React.StrictMode>
);
