// eslint-disable-next-line no-unused-vars
import React, { Suspense, useState } from 'react';
import {
	ToggleControl,
	TextControl,
	SelectControl,
} from '@wordpress/components';
import { cleanForSlug } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { useForm, Controller, useWatch, useFormState } from 'react-hook-form';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTriangleExclamation as TriangleExclamation, faCircleCheck as CircleCheck, faInfoCircle as Info } from '@fortawesome/free-solid-svg-icons';

// Local imports.
import SendCommand from '../../utils/SendCommand';
import Notice from '../../components/Notice';
import SaveResetButtons from '../../components/SaveResetButtons';

// Get admin options.
const adminOptions = dlxAppSettings.options;
const postTypes = dlxAppSettings.postTypes;
const taxonomies = dlxAppSettings.taxonomies;

const Settings = ( props ) => {
	const [ licenseValid ] = useState( adminOptions.licenseValid );

	const {
		control,
		handleSubmit,
		getValues,
		reset,
		setError,
		trigger,
		setValue,
	} = useForm( {
		defaultValues: {
			enablePostTypeArchiveMapping: adminOptions.enablePostTypeArchiveMapping,
			enableTermMapping: adminOptions.enableTermMapping,
			enableAuthorMapping: adminOptions.enableAuthorMapping,
			enable404Mapping: adminOptions.enable404Mapping,
			authorBase: adminOptions.authorBase,
			enableCustomFieldsRestSupport: adminOptions.enableCustomFieldsRestSupport,
			taxonomies: taxonomies ?? [],
			postTypes: postTypes ?? [],
			getNonce: dlxAppSettings.getNonce,
			saveNonce: dlxAppSettings.saveNonce,
			resetNonce: dlxAppSettings.resetNonce,
		},
	} );
	const formValues = useWatch( { control } );
	const { errors, isDirty, dirtyFields } = useFormState( {
		control,
	} );

	// Retrieve a prompt based on the license status.
	const getPrompt = () => {
		// Check to see if the license nag is disabled.
		if ( 'valid' === licenseValid && ! getValues( 'enableLicenseAlerts' ) ) {
			return null;
		}
		if ( 'valid' === licenseValid ) {
			return (
				<Notice
					message={ __( 'Thank you for supporting this plugin. Your license key is active and you are receiving updates and support.', 'archive-pages-pro' ) }
					status="success"
					politeness="assertive"
					inline={ false }
					icon={ () => <FontAwesomeIcon icon={ CircleCheck } style={ { color: 'currentColor' } } /> }
				/>
			);
		}
		return (
			<Notice
				message={ __( 'Your license key is not active. Please activate your license key to receive updates and support.', 'archive-pages-pro' ) }
				status="warning"
				politeness="assertive"
				inline={ false }
				icon={ () => <FontAwesomeIcon size="1x" icon={ TriangleExclamation } style={ { color: 'currentColor' } } /> }
			/>
		);
	};

	const getPostTypes = () => {
		// If there are no post types, return early.
		if ( postTypes.length === 0 ) {
			return (
				<Notice
					message={ __( 'There are no public post types to configure. Please note that core post types are removed from this list.', 'archive-pages-pro' ) }
					status="info"
					politeness="assertive"
					inline={ false }
					icon={ () => <FontAwesomeIcon size="1x" icon={ Info } style={ { color: 'currentColor' } } /> }
				/>
			);
		}
		return Object.values( getValues( 'postTypes' ) ).map( ( postType ) => {
			return (
				<div
					className="dlx-admin__row"
					key={ postType.name }
				>
					<h3>{ postType.label }</h3>
					<Controller
						name={ `postTypes.${ postType.name }.enable_page_templates` }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<ToggleControl
								label={ __( 'Enable Page Templates' ) }
								checked={ value }
								onChange={ ( boolValue ) => {
									onChange( boolValue );
								} }
								help={ __( 'Enable page templates for this post type.', 'archive-pages-pro' ) }
							/>
						) }
					/>
					<Controller
						name={ `postTypes.${ postType.name }.enable_show_in_rest` }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<ToggleControl
								label={ __( 'Show in REST API', 'archive-pages-pro' ) }
								checked={ value }
								onChange={ ( boolValue ) => {
									onChange( boolValue );
								} }
								help={ __( 'Enable this post type to show in the REST API. This is useful for enabling the block editor for a post type.', 'archive-pages-pro' ) }
							/>
						) }
					/>
					<Controller
						name={ `postTypes.${ postType.name }.enable_with_front` }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<ToggleControl
								label={ __( 'Enable a Front Base for the Post Type', 'archive-pages-pro' ) }
								checked={ value }
								onChange={ ( boolValue ) => {
									onChange( boolValue );
								} }
								help={ __( 'If you have a permalink like /blog/, then unless the post type specifies, the /blog/ will be its base. Disable this option if you do not want to use a base for your post type permalinks.', 'archive-pages-pro' ) }
							/>
						) }
					/>
					<Controller
						name={ `postTypes.${ postType.name }.enable_has_archive` }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<ToggleControl
								label={ __( 'Enable a Post Type Archive', 'archive-pages-pro' ) }
								checked={ value }
								onChange={ ( boolValue ) => {
									onChange( boolValue );
								} }
								help={ __( 'Enable this if you would like your post type to have an archive.', 'archive-pages-pro' ) }
							/>
						) }
					/>
					<Controller
						name={ `postTypes.${ postType.name }.enable_block_editor` }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<ToggleControl
								label={ __( 'Bypass the Classic Editor', 'archive-pages-pro' ) }
								checked={ value }
								onChange={ ( boolValue ) => {
									onChange( boolValue );
								} }
								help={ __( 'Enable this to force a post type to use the block editor.', 'archive-pages-pro' ) }
							/>
						) }
					/>
				</div>
			);
		} );
	};

	const getTaxonomies = () => {
		// If there are no post types, return early.
		if ( taxonomies.length === 0 ) {
			return (
				<Notice
					message={ __( 'There are no taxonomies to configure. Please note that core taxonomies are not included.', 'archive-pages-pro' ) }
					status="info"
					politeness="assertive"
					inline={ false }
					icon={ () => <FontAwesomeIcon size="1x" icon={ Info } style={ { color: 'currentColor' } } /> }
				/>
			);
		}
		return Object.values( getValues( 'taxonomies' ) ).map( ( taxonomy ) => {
			return (
				<div
					className="dlx-admin__row"
					key={ taxonomy.name }
				>
					<h3>{ taxonomy.label }</h3>
					<Controller
						name={ `taxonomies.${ taxonomy.name }.enable_show_in_rest` }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<ToggleControl
								label={ __( 'Show in REST API', 'archive-pages-pro' ) }
								checked={ value }
								onChange={ ( boolValue ) => {
									onChange( boolValue );
								} }
								help={ __( 'Enable this post type to show in the REST API. This is useful for enabling the block editor for a taxoomy.', 'archive-pages-pro' ) }
							/>
						) }
					/>
					<Controller
						name={ `taxonomies.${ taxonomy.name }.enable_with_front` }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<ToggleControl
								label={ __( 'Enable a Front Base for the Taxonomy', 'archive-pages-pro' ) }
								checked={ value }
								onChange={ ( boolValue ) => {
									onChange( boolValue );
								} }
								help={ __( 'If you have a permalink like /blog/, then unless the taxonomy specifies, the /blog/ will be its base. Disable this option if you do not want to use a base for your taxonomy term permalinks.', 'archive-pages-pro' ) }
							/>
						) }
					/>
					<Controller
						name={ `taxonomies.${ taxonomy.name }.disable_archive` }
						control={ control }
						render={ ( { field: { onChange, value } } ) => (
							<ToggleControl
								label={ __( 'Disable Taxonomy Archive', 'archive-pages-pro' ) }
								checked={ value }
								onChange={ ( boolValue ) => {
									onChange( boolValue );
								} }
								help={ __( 'Disable this if you would not like your taxonomy to have an archive.', 'archive-pages-pro' ) }
							/>
						) }
					/>
				</div>
			);
		} );
	};
	return (
		<>
			<div className="dlx-app-admin-content-heading">
				<h1><span className="dlx-app-content-heading-text">{ __( 'Settings for Archive Pages Pro', 'archive-pages-pro' ) }</span></h1>
				<p className="description">
					{
						__( 'Configure the settings below for various additions to Archive Pages Pro.', 'archive-pages-pro' )
					}
				</p>
				{
					getPrompt()
				}
			</div>
			{ /* eslint-disable-next-line no-unused-vars */ }
			<form onSubmit={ handleSubmit( ( formData ) => { } ) }>
				<div id="dlx-app-admin-table">
					<table className="form-table form-table-row-sections">
						<tbody>
							<tr>
								<th scope="row">
									{ __( 'Archive Mapping', 'archive-pages-pro' ) }
								</th>
								<td>
									<div className="dlx-admin__row">
										<Controller
											name="enablePostTypeArchiveMapping"
											control={ control }
											render={ ( { field: { onChange } } ) => (
												<ToggleControl
													label={ __( 'Enable Post Type Mapping', 'archive-pages-pro' ) }
													checked={ getValues( 'enablePostTypeArchiveMapping' ) }
													onChange={ ( boolValue ) => {
														onChange( boolValue );
													} }
													help={ __( 'Disabling this will turn off page mapping for post type archives.', 'archive-pages-pro' ) }
												/>
											) }
										/>
									</div>
									<div className="dlx-admin__row">
										<Controller
											name="enableTermMapping"
											control={ control }
											render={ ( { field: { onChange } } ) => (
												<ToggleControl
													label={ __( 'Enable Term Mapping', 'archive-pages-pro' ) }
													checked={ getValues( 'enableTermMapping' ) }
													onChange={ ( boolValue ) => {
														onChange( boolValue );
													} }
													help={ __( 'Disabling this will turn off page mapping for term archives.', 'archive-pages-pro' ) }
												/>
											) }
										/>
									</div>
									<div className="dlx-admin__row">
										<Controller
											name="enable404Mapping"
											control={ control }
											render={ ( { field: { onChange } } ) => (
												<ToggleControl
													label={ __( 'Enable 404 Mapping', 'archive-pages-pro' ) }
													checked={ getValues( 'enable404Mapping' ) }
													onChange={ ( boolValue ) => {
														onChange( boolValue );
													} }
													help={ __( 'Disabling this will turn off page mapping for 404 pages.', 'archive-pages-pro' ) }
												/>
											) }
										/>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row">
									{ __( 'Author Mapping', 'archive-pages-pro' ) }
								</th>
								<td>
									<div className="dlx-admin__row">
										<Controller
											name="enableAuthorMapping"
											control={ control }
											render={ ( { field: { onChange } } ) => (
												<ToggleControl
													label={ __( 'Enable Author Mapping', 'archive-pages-pro' ) }
													checked={ getValues( 'enableAuthorMapping' ) }
													onChange={ ( boolValue ) => {
														onChange( boolValue );
													} }
													help={ __( 'Disabling this will turn off page mapping for author archives.', 'archive-pages-pro' ) }
												/>
											) }
										/>
									</div>
									<div className="dlx-admin__row">
										<Controller
											name="authorBase"
											control={ control }
											rules={ {
												pattern: /^[a-zA-Z0-9_-]*$/,
											} }
											render={ ( { field: { onChange } } ) => (
												<>
													<TextControl
														label={ __( 'Author Base', 'archive-pages-pro' ) }
														value={ getValues( 'authorBase' ) }
														onChange={ ( value ) => {
															onChange( value );
														} }
														help={ __( 'The base for author archives. Default is "author". Leave this blank for no override of the author base.', 'archive-pages-pro' ) }
														onBlur={ () => {
															setValue( 'authorBase', cleanForSlug( getValues( 'authorBase' ).toLowerCase() ) );
															trigger( 'authorBase' );
														} }
													/>
													{
														errors?.authorBase?.type === 'pattern' && (
															<Notice
																message={ __( 'The author base must contain only letters, numbers, underscores, and hyphens.', 'archive-pages-pro' ) }
																status="error"
																politeness="assertive"
																inline={ true }
																icon={ () => <FontAwesomeIcon icon={ TriangleExclamation } style={ { color: 'currentColor' } } /> }
															/>
														)
													}
												</>
											) }
										/>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row">
									{ __( 'Custom Fields Overrides', 'archive-pages-pro' ) }
								</th>
								<td>
									<div className="dlx-admin__row">
										<Controller
											name="enableCustomFieldsRestSupport"
											control={ control }
											render={ ( { field: { onChange } } ) => (
												<ToggleControl
													label={ __( 'Enable Custom Fields REST API Support', 'archive-pages-pro' ) }
													checked={ getValues( 'enableCustomFields' ) }
													onChange={ ( boolValue ) => {
														onChange( boolValue );
													} }
													help={ __( 'Some blocks with dynamic data have trouble searching for custom fields that are not enabled for the REST API.', 'archive-pages-pro' ) }
												/>
											) }
										/>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row">
									{ __( 'Post Type Overrides', 'archive-pages-pro' ) }
								</th>
								<td>
									{ getPostTypes() }
								</td>
							</tr>
							<tr>
								<th scope="row">
									{ __( 'Taxonomy Overrides', 'archive-pages-pro' ) }
								</th>
								<td>
									{ getTaxonomies() }
								</td>
							</tr>
						</tbody>
					</table>
					<SaveResetButtons
						formValues={ formValues }
						setError={ setError }
						reset={ reset }
						errors={ errors }
						isDirty={ isDirty }
						dirtyFields={ dirtyFields }
						trigger={ trigger }
					/>
				</div>
			</form>
		</>
	);
};

export default Settings;
