import { Head } from "@inertiajs/react";
import React from "react";

interface HelloWorldProps {
    name: string;
    className?: string;
  }
  const HelloWorld: React.FC<HelloWorldProps> = ({ name, className }) => {
    return <div className={className}>Hello, {name}!</div>;
  };
  const Home: React.FC = () => {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <Head>
          <title>Hello Inertia</title>
        </Head>

        <HelloWorld className='text-center' name="Inertia.js" />
      </div>
    );
  };
  export default Home;