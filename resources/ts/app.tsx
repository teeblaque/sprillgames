import '../js/bootstrap';
import '../css/style.css';
import '../css/satoshi.css';
import '../css/app.css';


import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import React from "react";
import DefaultLayout from "./layouts/DefaultLayout";


createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true })
    let page: any = pages[`./Pages/${name}.tsx`];
    page.default.layout =
        page.default.layout || ((page: any) => <DefaultLayout children={page} />);

    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
