import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Link2 } from 'lucide-react';
import URLPicker from '../../components/URLPicker';

// post-type-archive-mapping is the name of the input field so that the data is saved with the settings API and for backwards compatibility with Custom Query Blocks.
const SettingsReadingPostType = ( { postType } ) => {
	const [ mappedValue, setMappedValue ] = useState( postType.mapped );

	return (
		<div className="settings-reading__post-type" key={ postType.value }>
			<h4>
				{ __( 'Map Post Type:', 'archive-pages-pro' ) } <a href={ postType.archiveUrl } target="_blank" rel="noreferrer noopener">{ postType.label }</a>
			</h4>
			{
				<>
					<input type="hidden" name={ `post-type-archive-mapping[${ postType.value }]` } value={ mappedValue } />
				</>
			}
			<URLPicker
				restNonce={ appSettingsReading.restNonce }
				restEndpoint={ appSettingsReading.pageRestUrl }
				itemIcon={ <Link2 /> }
				onItemSelect={ ( e, id_or_default ) => {
					setMappedValue( id_or_default );
				} }
				mappedPageId={ postType.mapped }
				savedValue={ postType.title }
				savedTitle={ postType.title }
			/>
		</div>
	);
};
export default SettingsReadingPostType;
