import { useEffect, useState } from "react";
import { connect } from 'react-redux';
import { fetchEmailSettings } from "../../state/emailFunctions";
import { Button } from "react-bootstrap";
import store from "../../state/store";
import { setEmailValues } from "../../state/emailActions";


const EmailComposePage = ({isLoading, emailTo, emailFrom, emailCC, emailValues, onNext}) => {

    const [ to, setTo ] = useState(emailValues?.to);
    const [ from, setFrom ] = useState(emailValues?.from);
    const [ cc, setCc ] = useState(emailValues?.cc);
    const [ replyTo, setReplyTo ] = useState(emailValues?.replyTo);
    const [ subject, setSubject ] = useState(emailValues?.subject ?? "");
    const [ text, setText ] = useState(emailValues?.text ?? "");

    useEffect(() => {
        if (isLoading) {
            fetchEmailSettings((emailTo, emailFrom, emailCC) => {
                if (emailTo) {
                    setTo(emailTo[0].id);
                }
                if (emailFrom) {
                    setFrom(emailFrom[0].id);
                }
            });
        }
    }, []);

    const navigateToNextPage = () => {
        store.dispatch(setEmailValues({
            to: to,
            from: from,
            cc: cc,
            replyTo: replyTo,
            subject: subject,
            text: text
        }));
        onNext();
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
                    <select className="form-control" name="sendto" value={to ?? ""} onChange={(e) => setTo(e.target.value)}>
                        {emailTo.map(e => (<option value={e.id} key={'email-to-' + e.id}>{e.name}</option>))}
                    </select>
                </div>
            </div>

            <div className="form-group row">
                <div className="col-md-2">
                    <label htmlFor="sendfrom">From: </label>
                </div>
                <div className="col-md-6">
                    <select className="form-control" name="sendfrom" value={from ?? ""} onChange={(e) => setFrom(e.target.value)}>
                        {emailFrom.map(e => (<option value={e.id} key={'email-from-' + e.id}>{e.name}</option>))}
                    </select>
                </div>
            </div>

            <div className="form-group row">
                <div className="col-md-2">
                    <label htmlFor="sendcc">CC: </label>
                </div>
                <div className="col-md-6">
                    <select className="form-control" name="sendcc" value={cc ?? ""} onChange={(e) => setCc(e.target.value)}>
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
                    <select className="form-control" name="sendreplyto" value={replyTo ?? ""}  onChange={(e) => setReplyTo(e.target.value)}>
                        <option>None</option>
                        {emailCC.map(e => (<option value={e.id} key={'email-reply-' + e.id}>{e.name}</option>))}
                    </select>
                </div>
            </div>

            <div className="form-group">
                <label htmlFor="subject" className="sr-only">Subject: </label>
                <input className="form-control" name="subject" type="text" size="40" placeholder="Subject..." value={subject} onChange={(e) => setSubject(e.target.value)} />
            </div>

            <div className="form-group">
                <label htmlFor="subject" className="sr-only">Body: </label>
                <textarea name="body" className="form-control" rows="25" value={text} onChange={(e) => setText(e.target.value)}>
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
            <Button variant="primary" onClick={() => navigateToNextPage()}>Review</Button>
        </div>
    </div>);
}

function mapStateToProps(state) {
    return {
        isLoading: state.email.loading,
        emailTo: state.email.emailTo,
        emailFrom: state.email.emailFrom,
        emailCC: state.email.emailCC,
        emailValues: state.email.emailValues
    };
}

export default connect(mapStateToProps)(EmailComposePage);