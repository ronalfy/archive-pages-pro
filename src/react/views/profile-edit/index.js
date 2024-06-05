import React from 'react';
import { createRoot } from 'react-dom/client';
import ProfileEdit from './profile-edit';

const container = document.getElementById( 'app-author-mapping' );

if ( null !== container ) {
	const root = createRoot( container );
	root.render(
		<React.StrictMode>
			<ProfileEdit />
		</React.StrictMode>
	);
}
