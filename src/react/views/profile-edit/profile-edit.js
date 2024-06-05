import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import './styles.scss';
import {
	TextControl
} from '@wordpress/components';

import {
	cleanForSlug
} from '@wordpress/url';
import { Link2 } from 'lucide-react';
import URLPicker from '../../components/URLPicker';

const mappedProfilePageId = 'default' !== appSettingsReading.pageIdProfile ? parseInt( appSettingsReading.pageIdProfile ) : 0;

const ProfileEdit = () => {
	const [ mappedProfileValue, setMappedProfileValue ] = useState( mappedProfilePageId );
	const [ profileSlug, setProfileSlug ] = useState( appSettingsReading.nicename );
	return (
		<div className="settings-reading">
			<div className="settings-reading__post-type">
				<div className="form-field">
					<TextControl
						label={ __( 'Author Archive Slug:', 'archive-pages-pro' ) }
						value={ profileSlug }
						onChange={ ( value ) => {
							setProfileSlug( value );
						} }
						onBlur={ () => {
							setProfileSlug( cleanForSlug( profileSlug ) );
						} }
						className="regular-text"
					/>
					<input type="hidden" name="app-user-profile[slug]" value={ cleanForSlug( profileSlug ) } />
				</div>
			</div>
			<div className="settings-reading__post-type">
				<h4>
					{ __( 'Map Author Archive Page:', 'archive-pages-pro' ) }
				</h4>
				{
					<>
						<input type="hidden" name={ `app-user-profile[page_id]` } value={ parseInt( mappedProfileValue ) } />
					</>
				}
				<URLPicker
					restNonce={ appSettingsReading.restNonce }
					restEndpoint={ appSettingsReading.restEndpoint }
					itemIcon={ <Link2 /> }
					onItemSelect={ ( e, id_or_default ) => {
						setMappedProfileValue( id_or_default );
					} }
					mappedPageId={ mappedProfileValue }
					savedValue={ appSettingsReading.pageTitleProfile }
					savedTitle={ appSettingsReading.pageTitleProfile }
				/>
			</div>
		</div>
	);
};
export default ProfileEdit;
