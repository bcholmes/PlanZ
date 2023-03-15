import React, { useState } from "react";
import { Button } from "react-bootstrap";
import InitialDataView from "./initialDataView";
import MetricsView from "./metricsView";

const AutoSchedulePage = () => {

    const [step, setStep] = useState(0);

    const showStep = () => {
        if (step === 1) {
            return (<InitialDataView />)
        } else if (step === 0) {
            return (<MetricsView />);
        } else {
            return null;
        }
    }

    return (<div className="card mb-3">
        <div className="card-header">
            <h2>Auto-Scheduler</h2>
        </div>
        <div className="card-body">
            {showStep()}
        </div>
        <div className="card-footer text-right">
            <Button variant="primary" onClick={() => setStep(step+1)}>Next</Button>
        </div>
    </div>);

}

export default AutoSchedulePage;