import { useEffect } from "react";
import { connect } from 'react-redux';
import { fetchEmailSettings } from "../../state/emailFunctions";
import { Button } from "react-bootstrap";


const EmailComposePage = ({isLoading, emailTo, emailFrom, emailCC, onNext}) => {

    useEffect(() => {
        if (isLoading) {
            fetchEmailSettings();
        }
    }, []);

    const setReplyTo = (value) => {
        console.log(value);
    }

    return (<div className="card">
        <div className="card-header">
            <h3>Step 1 &mdash; Compose Email</h3>
        </div>
        <div className="card-body">

            <div className="form-group row">
                <div className="col-md-2">
                    <label htmlFor="sendto">To: </label>
                </div>
                <div className="col-md-6">
                    <select className="form-control" name="sendto">
                        {emailTo.map(e => (<option value={e.id} key={'email-to-' + e.id}>{e.name}</option>))}
                    </select>
                </div>
            </div>

            <div className="form-group row">
                <div className="col-md-2">
                    <label htmlFor="sendfrom">From: </label>
                </div>
                <div className="col-md-6">
                    <select className="form-control" name="sendfrom">
                        {emailFrom.map(e => (<option value={e.id} key={'email-from-' + e.id}>{e.name}</option>))}
                    </select>
                </div>
            </div>

            <div className="form-group row">
                <div className="col-md-2">
                    <label htmlFor="sendcc">CC: </label>
                </div>
                <div className="col-md-6">
                    <select className="form-control" name="sendcc">
                        <option>None</option>
                        {emailCC.map(e => (<option value={e.id} key={'email-cc-' + e.id}>{e.name}</option>))}
                    </select>
                </div>
            </div>

            <div className="form-group row">
                <div className="col-md-2">
                    <label htmlFor="sendreplyto">Reply to:</label>
                </div>
                <div className="col-md-6">
                    <select className="form-control" name="sendreplyto" onChange={(e) => setReplyTo(e.target.value)}>
                        <option>None</option>
                        {emailCC.map(e => (<option value={e.id} key={'email-reply-' + e.id}>{e.name}</option>))}
                    </select>
                </div>
            </div>

            <div className="form-group">
                <label htmlFor="subject" className="sr-only">Body: </label>
                <textarea name="body" className="form-control" rows="25">
                </textarea>
            </div>

            <div>

                <p>Available substitutions:</p>
                <table className="multcol-list">
                    <tbody>
                        <tr><td>$BADGEID$</td><td>$EMAILADDR$</td></tr>
                        <tr><td>$FIRSTNAME$</td><td>$PUBNAME$</td></tr>
                        <tr><td>$LASTNAME$</td><td>$BADGENAME$</td></tr>
                        <tr><td>$EVENTS_SCHEDULE$</td><td>$FULL_SCHEDULE$</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div className="card-footer text-right">
            <Button variant="primary" onClick={() => onNext()}>Review</Button>
        </div>
    </div>);
}

function mapStateToProps(state) {
    return {
        isLoading: state.email.loading,
        emailTo: state.email.emailTo,
        emailFrom: state.email.emailFrom,
        emailCC: state.email.emailCC,
    };
}

export default connect(mapStateToProps)(EmailComposePage);