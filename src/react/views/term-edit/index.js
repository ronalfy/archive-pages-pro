import React from 'react';
import { createRoot } from 'react-dom/client';
import TermEdit from './term-edit';

const container = document.getElementById( 'app-term-mapping' );

if ( null !== container ) {
	const root = createRoot( container );
	root.render(
		<React.StrictMode>
			<TermEdit />
		</React.StrictMode>
	);
}
