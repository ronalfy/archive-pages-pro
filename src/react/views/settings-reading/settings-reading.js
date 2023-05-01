import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import ContentPicker from '../../../react/components/content-picker';
import FileTextIcon from '../../icons/file-text';
const SettingsReading = () => {

	const [ selectedPageValue, setSelectedPageValue ] = useState( null );
	const [ selectedPageLabel, setSelectedPageLabel ] = useState( __( 'None', 'archive-pages-pro' ) );
	return (
		<div className="settings-reading">
			<ContentPicker
				restEndpoint={ appSettingsReading.pageRestUrl }
				restNonce={ appSettingsReading.restNonce }
				id="edd-pub-add-new-download-input"
				title={ __( 'Selected Page', 'archive-pages-pro' ) }
				label={ __( 'Search for a Page', 'archive-pages-pro' ) }
				hasInititialFocus={ true }
				onItemSelect={ ( event, item ) => {
					setSelectedPageValue( item.value );
					setSelectedPageLabel( item.label );
				} }
				itemIcon={ <FileTextIcon width={ 24 } height={ 24 } fill="none" /> }
			/>
		</div>
	);
};
export default SettingsReading;
