import { useEffect, useState } from "react";
import { Button } from "react-bootstrap";
import { sendEmails } from "../../state/emailFunctions";
import LoadingButton from "../../common/loadingButton";
import authAxios from "../../common/authAxios";
import toast from 'react-hot-toast';
import { connect } from "react-redux";

const EmailReviewPage = ({onNext, onPrevious, emailValues}) => {

    const [ loading, setLoading ] = useState(false);
    const [ recipients, setRecipients ] = useState([]);
    const [ sampleText, setSampleText ] = useState("");

    const navigateToNextPage = () => {
        setLoading(true);

        sendEmails((ok) => {
            setLoading(false);
            if (ok) {
                onNext();
            }
        });
    }

    useEffect(() => {
        authAxios.post('/api/email/preview_email.php', emailValues)
            .then(res => {
                setRecipients(res.data?.recipients ?? []);
                setSampleText(res.data?.text ?? "");
            })
            .catch(error => {
                toast("There was an error trying to preview the email. Try again later?", { className: "bg-danger text-white" });
            }
        );
    }, []);

    const emailList = () => {
        return recipients.map(r => (r.name ? r.name  + " - " : "") + r.address).join("\n");
    }

    return (<div className="card">
        <div className="card-header">
            <h3>Step 2 &mdash; Review Email</h3>
        </div>
        <div className="card-body">

            <div className="form-group">
                <label htmlFor="recipients">Recipient List: </label>
                <textarea name="recipients" id="recipients" className="form-control" rows="10" value={emailList()} readOnly={true}>
                </textarea>
            </div>

            <p>Here's how the email is rendered for the first recipient.</p>

            <div className="form-group">
                <label htmlFor="sampleText" className="sr-only">Sample text: </label>
                <textarea name="sampleText" id="sampleText" className="form-control" rows="20" value={sampleText} readOnly={true}>
                </textarea>
            </div>
        </div>
        <div className="card-footer d-flex justify-content-between">
            <Button variant="outline-primary" onClick={() => onPrevious()}>Previous</Button>
            <LoadingButton variant="primary" onClick={() => navigateToNextPage()} loading={loading} enabled={true}>Send</LoadingButton>
        </div>
    </div>)
}

function mapStateToProps(state) {
    return {
        emailValues: state.email.emailValues
    };
}

export default connect(mapStateToProps)(EmailReviewPage);
