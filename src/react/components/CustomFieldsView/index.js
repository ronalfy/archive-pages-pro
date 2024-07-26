import React, { useEffect, useState } from 'react';
import { Button, Modal, TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useForm, Controller, useWatch, useFormState } from 'react-hook-form';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTriangleExclamation as TriangleExclamation, faCircleCheck as CircleCheck, faInfoCircle as Info } from '@fortawesome/free-solid-svg-icons';
import Notice from '../Notice';

const objectTypes = dlxAppSettings.objectTypes;
const CustomFieldsView = ( { data, onChange } ) => {
	const [ customFields, setCustomFields ] = useState( data );
	const [ launchModal, setLaunchModal ] = useState( false );
	const [ launchDeleteConfirmation, setLaunchDeleteConfirmation ] = useState( false );

	const {
		control,
		handleSubmit,
		getValues,
		reset,
		setError,
		trigger,
		setValue,
		clearErrors,
	} = useForm( {
		defaultValues: {
			customField: '',
			objectType: '',
			variableType: '',
		},
	} );
	const formValues = useWatch( { control } );
	const { errors, isDirty, dirtyFields } = useFormState( {
		control,
	} );

	useEffect( () => {
		setCustomFields( data );
	}, [ data ] );

	return (
		<>
			{ launchDeleteConfirmation && (
				<Modal
					title={ __( 'Delete Custom Field', 'archive-pages-pro' ) }
					onRequestClose={ () => setLaunchDeleteConfirmation( false ) }
				>
					<div className="app-custom-fields-modal">
						<p>
							{ __( 'Are you sure you want to delete this custom field?', 'archive-pages-pro' ) }
						</p>
						<div className="app-custom-fields-modal-row">
							<Button
								variant="primary"
								isDestructive={ true }
								onClick={ () => {
									// Set items.
									setCustomFields( launchDeleteConfirmation );
									onChange( launchDeleteConfirmation );

									// Close modal.
									setLaunchDeleteConfirmation( false );
								} }
							>
								{ __( 'Delete Custom Field', 'archive-pages-pro' ) }
							</Button>
							<Button
								variant="secondary"
								onClick={ () => {
									setLaunchDeleteConfirmation( false );
								} }
							>
								{ __( 'Close', 'archive-pages-pro' ) }
							</Button>
						</div>
					</div>
				</Modal>
			) }
			{ launchModal && (
				<Modal
					title={ __( 'Edit Custom Field', 'archive-pages-pro' ) }
					onRequestClose={ () => setLaunchModal( false ) }
				>
					<div className="app-custom-fields-modal">
						<div className="app-custom-fields-modal-row">
							<Controller
								name="customField"
								control={ control }
								rules={ {
									pattern: /^[a-zA-Z0-9_-]*$/,
								} }
								render={ ( { field } ) => (
									<TextControl
										label={ __( 'Field', 'archive-pages-pro' ) }
										{ ...field }
									/>
								) }
							/>
							{ errors.customField && (
								<Notice
									status="error"
									politeness="assertive"
									inline={ true }
									icon={ () => <FontAwesomeIcon icon={ TriangleExclamation } style={ { color: 'currentColor' } } /> }
									message={ __( 'Custom field names can only contain letters, numbers, underscores, and hyphens.', 'archive-pages-pro' ) }
								/>
							) }
						</div>
						<div className="app-custom-fields-modal-row">
							<Controller
								name="objectType"
								control={ control }
								render={ ( { field } ) => (
									<SelectControl
										label={ __( 'Object Type', 'archive-pages-pro' ) }
										value={ field.value ?? 'post' }
										onChange={ ( newValue ) => {
											field.onChange( newValue );
										} }
										options={
											Object.values( objectTypes ).map( ( objectType ) => {
												return {
													value: objectType.name,
													label: objectType.label,
												};
											} )
										}
										help={ __( 'The type of object that this custom field is attached to.', 'archive-pages-pro' ) }
									/>
								) }
							/>
						</div>
						<div className="app-custom-fields-modal-row">
							<Controller
								name="variableType"
								control={ control }
								render={ ( { field } ) => (
									<SelectControl
										label={ __( 'Variable Type', 'archive-pages-pro' ) }
										value={ field.value ?? 'string' }
										onChange={ ( newValue ) => {
											field.onChange( newValue );
										} }
										options={
											[
												{
													value: 'string',
													label: __( 'String', 'archive-pages-pro' ),
												},
												{
													value: 'number',
													label: __( 'Number', 'archive-pages-pro' ),
												},
												{
													value: 'boolean',
													label: __( 'Boolean', 'archive-pages-pro' ),
												},
											]
										}
										help={ __( 'The data type for the custom field. Only strings, numbers, and booleans are supported.', 'archive-pages-pro' ) }
									/>
								) }
							/>
						</div>
						<div className="app-custom-fields-modal-row">
							<Button
								variant="secondary"
								onClick={ async() => {
									const result = await trigger( 'customField' );
									if ( result ) {
										if ( ! errors?.customField ) {
											const updateIndex = launchModal.index;
											const newCustomFields = { ...customFields };
											newCustomFields[ updateIndex ] = getValues();

											// Re-index into array.
											const newCustomFieldsArray = [];
											Object.values( newCustomFields ).forEach( ( value ) => {
												newCustomFieldsArray.push( value );
											} );

											setCustomFields( newCustomFieldsArray );

											// Close modal.
											setLaunchModal( false );
											onChange( newCustomFieldsArray );
										}
									}
								} }
							>
								{ __( 'Add Custom Field', 'archive-pages-pro' ) }
							</Button>
						</div>
					</div>
				</Modal>
			) }
			<table className="wp-list-table widefat fixed striped table-view-list inner-form">
				<thead>
					<tr>
						<th scope="col" className="manage-column">{ __( 'Field', 'archive-pages-pro' ) }</th>
						<th scope="col" className="manage-column">{ __( 'Object Type', 'archive-pages-pro' ) }</th>
						<th scope="col" className="manage-column">{ __( 'Variable type', 'archive-pages-pro' ) }</th>
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
										onClick={ () => {
											setValue( 'customField', field.customField );
											setValue( 'objectType', field.objectType );
											setValue( 'variableType', field.variableType );

											setLaunchModal( {
												field,
												index,
											} );
										} }
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

												if ( customFieldValues.customField === field.customField && customFieldValues.objectType === field.objectType ) {
													delete newCustomFields[ key ];
												}
											} );

											// Re-index into array.
											const newCusomFieldArray = [];
											Object.values( newCustomFields ).forEach( ( value ) => {
												newCusomFieldArray.push( value );
											} );

											setLaunchDeleteConfirmation( newCusomFieldArray );
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
		</>
	);
};

export default CustomFieldsView;
