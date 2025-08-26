import React, { useState } from "react";
import { Button } from "react-bootstrap";
import InitialDataView from "./initialDataView";
import MetricsView from "./metricsView";
import { AutoSchedulerIntroduction } from "./autoSchedulerIntroductionView";

const AutoSchedulePage: React.FC<{}> = () => {

    const [step, setStep] = useState<number>(0);

    const showStep = () => {
        if (step === 0) {
            return (<AutoSchedulerIntroduction />);
        } else if (step === 1) {
            return (<InitialDataView />)
        } else if (step === 2) {
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
        <div className="card-footer d-flex justify-content-between">
            <div>
                {step > 0 ? (<Button variant="secondary" onClick={() => setStep(step-1)}>Back</Button>) : undefined}
            </div>
            <Button variant="primary" onClick={() => setStep(step+1)}>Next</Button>
        </div>
    </div>);

}

export default AutoSchedulePage;