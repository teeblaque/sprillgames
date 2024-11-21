import React from 'react';

export default interface ReactFC extends React.FC {
    layout?: any; // Make the prop optional
}