import { useState } from "react";
import { Button } from "react-bootstrap";
import { sendEmails } from "../../state/emailFunctions";
import LoadingButton from "../../common/loadingButton";

const EmailReviewPage = ({onNext, onPrevious}) => {

    const [ loading, setLoading ] = useState(false);

    const navigateToNextPage = () => {
        setLoading(true);

        sendEmails((ok) => {
            setLoading(false);
            if (ok) {
                onNext();
            }
        });
    }

    return (<div className="card">
        <div className="card-header">
            <h3>Step 2 &mdash; Review Email</h3>
        </div>
        <div className="card-body">

        </div>
        <div className="card-footer d-flex justify-content-between">
            <Button variant="outline-primary" onClick={() => onPrevious()}>Previous</Button>
            <LoadingButton variant="primary" onClick={() => navigateToNextPage()} loading={loading} enabled={true}>Send</LoadingButton>
        </div>
    </div>)
}

export default EmailReviewPage;