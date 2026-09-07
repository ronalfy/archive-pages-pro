import React, { useEffect, useState } from 'react';
import { Button, Modal, TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useForm, Controller, useWatch, useFormState } from 'react-hook-form';
import { AlertCircle } from 'lucide-react';
import Notice from '../Notice';

const objectTypes = dlxAppSettings.objectTypes;
const CustomFieldsInput = ( { data, setCustomFieldValues, formSetValue } ) => {
	const [ customFields, setCustomFields ] = useState( data );
	const [ launchModal, setLaunchModal ] = useState( false );
	const [ launchDeleteConfirmation, setLaunchDeleteConfirmation ] = useState( false );
	const [ customFieldTextInput, setCustomFieldTextInput ] = useState( null );

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
			objectType: 'post',
			variableType: 'string',
		},
	} );
	const formValues = useWatch( { control } );
	const { errors, isDirty, dirtyFields } = useFormState( {
		control,
	} );

	useEffect( () => {
		setCustomFields( data );
	}, [ data ] );

	/**
	 * Callback on form submission.
	 *
	 * @param {Object} formData Form data.
	 */
	const onSubmit = async( formData ) => {
		if ( ! errors?.customField ) {
			const newCustomField = [ ...customFields ];

			// Make sure there aren't any duplicates in array.
			const customFieldExists = newCustomField.find( ( field ) => {
				if ( field === undefined ) {
					return false;
				}
				return field.customField === formData.customField && field.objectType === formData.objectType;
			} );
			if ( customFieldExists ) {
				setError( 'customField', {
					type: 'duplicate',
					message: __( 'This custom field already exists.', 'archive-pages-pro' ),
				} );
				customFieldTextInput.focus();
				return;
			}

			const customFieldsInput = {
				customField: formData.customField,
				objectType: formData.objectType,
				variableType: formData.variableType,
			};
			newCustomField.push( customFieldsInput );
			formSetValue( 'customFields', newCustomField );
			setCustomFieldValues( newCustomField );
			// Clear the input fields.
			setValue( 'customField', '' );
		}
	};

	return (
		<>
			<div className="dlx-admin__row">
				<form onSubmit={ handleSubmit( ( formData ) => {
					onSubmit( formData );
				} ) }>
					<Controller
						name="customField"
						rules={ {
							pattern: /^[a-zA-Z0-9_-]*$/,
						} }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<TextControl
								label={ __( 'Custom Field Name', 'archive-pages-pro' ) }
								className={
									classnames( {
										'has-error': errors?.customField?.type === 'pattern' || errors?.customField?.type === 'duplicate',
									} )
								}
								placeholder={ '_sample_custom_field' }
								value={ value }
								onChange={ ( newValue ) => {
									clearErrors( 'customField' );
									onChange( newValue );
								} }
								help={ __( 'The name of the custom field to enable for the REST API.', 'archive-pages-pro' ) }
								onBlur={ () => {
									trigger( 'customField' );
								} }
								ref={ setCustomFieldTextInput }
							/>
						) }
					/>
					{
						errors?.customField?.type === 'pattern' && (
							<Notice
								message={ __( 'The custom field name must contain only letters, numbers, underscores, and hyphens.', 'archive-pages-pro' ) }
								status="error"
								politeness="assertive"
								inline={ true }
								icon={ AlertCircle }
							/>
						)
					}
					{
						errors?.customField?.type === 'duplicate' && (
							<Notice
								message={ __( 'There appear to be duplicate custom fields.' ) }
								status="error"
								politeness="assertive"
								inline={ true }
								icon={ AlertCircle }
							/>
						)
					}
					<Controller
						name="objectType"
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<SelectControl
								label={ __( 'Object Type', 'archive-pages-pro' ) }
								value={ value ?? 'post' }
								onChange={ ( newValue ) => {
									onChange( newValue );
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
					<Controller
						name="variableType"
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<SelectControl
								label={ __( 'Variable Type', 'archive-pages-pro' ) }
								value={ value ?? 'string' }
								onChange={ ( newValue ) => {
									onChange( newValue );
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
					<Button
						variant="secondary"
						type="submit"
						onClick={ async( e ) => {
							e.preventDefault();
							const validationResult = await trigger();
							if ( validationResult ) {
								onSubmit( getValues() );
							}
						} }
					>
						{ __( 'Add Custom Field', 'archive-pages-pro' ) }
					</Button>
				</form>
			</div>
		</>
	);
};

export default CustomFieldsInput;
