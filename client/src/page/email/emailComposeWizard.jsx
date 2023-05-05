import { useState } from "react";
import EmailComposePage from "./emailComposePage";
import EmailReviewPage from "./emailReviewPage";
import EmailCompletePage from "./emailCompletePage";

const EmailComposeWizard = () => {

    const [ page, setPage ] = useState(0);


    const navigateToNextPage = () => {
        setPage(page+1)
    }

    if (page === 0) {
        return (<EmailComposePage onNext={() => navigateToNextPage()}/>);
    } else if (page === 1) {
        return (<EmailReviewPage onNext={() => navigateToNextPage()} onPrevious={() => setPage(page-1)}/>);
    } else if (page === 2) {
        return (<EmailCompletePage />);
    } else {
        return (<h4>Next</h4>);
    }
}

export default EmailComposeWizard;