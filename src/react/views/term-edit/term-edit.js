import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Link2 } from 'lucide-react';
import URLPicker from '../../components/URLPicker';

const TermEdit = () => {
	const [ mappedTermValue, setMappedTermValue ] = useState( parseInt( appTermEdit.termData?.value ) );
	return (
		<div className="settings-term-edit">
			<div className="settings-term-edit__wrapper">
				{
					<>
						<input type="hidden" name={ `term_post_type` } value={ mappedTermValue } />
					</>
				}
				<URLPicker
					restNonce={ appTermEdit.restNonce }
					restEndpoint={ appTermEdit.restUrl }
					itemIcon={ <Link2 /> }
					onItemSelect={ ( e, id_or_default ) => {
						setMappedTermValue( id_or_default );
					} }
					mappedPageId={ mappedTermValue }
					savedValue={ appTermEdit.termData?.title }
					savedTitle={ appTermEdit.termData?.title }
				/>
			</div>
		</div>
	);
};
export default TermEdit;
