import React, { useEffect, useState } from 'react';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
const CustomFieldsView = ( { data, onChange } ) => {
	const [ customFields, setCustomFields ] = useState( data );

	useEffect( () => {
		setCustomFields( data );
	}, [ data ] );

	return (
		<table className="app-custom-fields-table">
			<thead>
				<tr>
					<th>Field</th>
					<th>Object Type</th>
					<th>Variable type</th>
				</tr>
			</thead>
			<tbody>
				{ Object.values( customFields ).map( ( field, index ) => (
					<tr key={ index }>
						<td>
							{ field.customField }
							<div className="app-custom-fields-actions">
								<Button
									variant="link"
								>
									{ __( 'Edit', 'archive-pages-pro' ) }
								</Button>
								{ ' | ' }
								<Button
									variant="link"
									isDestructive={ true }
									onClick={ () => {
										const newCustomFields = { ...customFields };

										// Find matching field.
										Object.keys( newCustomFields ).forEach( ( key ) => {
											// Noew go through the object values and find a match.
											const customFieldValues = newCustomFields[ key ];

											if ( customFieldValues.customField === field.customField ) {
												delete newCustomFields[ key ];
											}
										} );

										// Re-index into array.
										const newCusomFieldArray = [];
										Object.values( newCustomFields ).forEach( ( value ) => {
											newCusomFieldArray.push( value );
										} );

										// Set items.
										setCustomFields( newCusomFieldArray );
										onChange( newCusomFieldArray );
									} }
								>
									{ __( 'Delete', 'archive-pages-pro' ) }
								</Button>
							</div>

						</td>
						<td>{ field.objectType }</td>
						<td>{ field.variableType }</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
};

export default CustomFieldsView;
