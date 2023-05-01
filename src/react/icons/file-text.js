import React from 'react';
import PropTypes from 'prop-types'; // ES6

const FileTextIcon = ( props ) => {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width={ props.width }
			height={ props.height }
			fill="none"
			stroke="currentColor"
			strokeLinecap="round"
			strokeLinejoin="round"
			strokeWidth={ 2 }
			className="lucide lucide-file-text"
		>
			<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
			<path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
		</svg>
	);
};
FileTextIcon.defaultProps = {
	width: 16,
	height: 16,
	fill: '#333333',
};

FileTextIcon.propTypes = {
	width: PropTypes.number,
	height: PropTypes.number,
	fill: PropTypes.string,
};

export default FileTextIcon;
