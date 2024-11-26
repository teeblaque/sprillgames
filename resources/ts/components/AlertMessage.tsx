
import React from 'react';
import { AlertCircle } from "lucide-react"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"

interface AlertMessageProps {
    message: string;
    type: AlertMessageType;
}

enum AlertMessageType {
    success,
    warning,
    error
}

const AlertMessage: React.FC<AlertMessageProps> = ({ type, message }) => {

    if (type == AlertMessageType.error) {
        return (<><Alert variant="destructive" className='mb-2 border-red'>
            <div className='flex'>
                <AlertCircle className="h-4 w-4 text-red mr-2 mt-1" />
                <AlertTitle className="text-red">Error</AlertTitle>
            </div>
            <AlertDescription className="text-red">
                {message}
            </AlertDescription>
        </Alert></>)
    }

    if (type == AlertMessageType.warning) {
        return (<><Alert variant="destructive" className='mb-2 border-warning'>
            <div className='flex'>
                <AlertCircle className="h-4 w-4 text-warning mr-2 mt-1" />
                <AlertTitle className="text-warning">Error</AlertTitle>
            </div>
            <AlertDescription className="text-warning">
                {message}
            </AlertDescription>
        </Alert></>)
    }

    return (<><Alert variant="destructive" className='mb-2 border-success'>
        <div className='flex'>
            <AlertCircle className="h-4 w-4 text-success mr-2 mt-1" />
            <AlertTitle className="text-success">Success</AlertTitle>
        </div>
        <AlertDescription className="text-success">
            {message}
        </AlertDescription>
    </Alert></>)



};


export { AlertMessage, AlertMessageType };
