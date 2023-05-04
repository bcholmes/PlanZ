import { useState } from "react";
import EmailComposePage from "./emailComposePage";
import EmailReviewPage from "./emailReviewPage";

const EmailComposeWizard = () => {

    const [ page, setPage ] = useState(0);


    const navigateToNextPage = () => {
        setPage(page+1)
    }

    if (page === 0) {
        return (<EmailComposePage onNext={() => navigateToNextPage()}/>);
    } else if (page === 1) {
        return (<EmailReviewPage onNext={() => navigateToNextPage()} onPrevious={() => setPage(page-1)}/>);
    } else {
        return (<h4>Next</h4>);
    }
}

export default EmailComposeWizard;