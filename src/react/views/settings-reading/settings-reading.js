import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import './styles.scss';
import { Link2 } from 'lucide-react';
import SettingsReadingPostType from '../../components/SettingsReadingPostType';
import URLPicker from '../../components/URLPicker';

const postTypes = appSettingsReading.postTypes;
const mapped404PageTitle = appSettingsReading.pageTitle404;
const SettingsReading = () => {
	const [ mapped404Value, setMapped404Value ] = useState( parseInt( appSettingsReading.pageId404 ) );
	return (
		<div className="settings-reading">
			{
				postTypes.map( ( postType ) => {
					return (
						<SettingsReadingPostType key={ postType.value } postType={ postType } />
					);
				} )
			}
			<div className="settings-reading__post-type">
				<h4>
					{ __( 'Map 404 Page:', 'archive-pages-pro' ) }
				</h4>
				{
					<>
						<input type="hidden" name={ `post-type-archive-mapping-404` } value={ mapped404Value } />
					</>
				}
				<URLPicker
					restNonce={ appSettingsReading.restNonce }
					restEndpoint={ appSettingsReading.pageRestUrl }
					itemIcon={ <Link2 /> }
					onItemSelect={ ( e, id_or_default ) => {
						setMapped404Value( id_or_default );
					} }
					mappedPageId={ mapped404Value }
					savedValue={ mapped404PageTitle }
					savedTitle={ mapped404PageTitle }
				/>
			</div>
		</div>
	);
};
export default SettingsReading;
