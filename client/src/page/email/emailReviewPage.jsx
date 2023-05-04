import { Button } from "react-bootstrap";

const EmailReviewPage = ({onNext, onPrevious}) => {

    return (<div className="card">
        <div className="card-header">
            <h3>Step 2 &mdash; Review Email</h3>
        </div>
        <div className="card-body">

        </div>
        <div className="card-footer d-flex justify-content-between">
            <Button variant="outline-primary" onClick={() => onPrevious()}>Previous</Button>
            <Button variant="primary" onClick={() => onNext()}>Send</Button>
        </div>
    </div>)
}

export default EmailReviewPage;