import axios from "axios";
import React, { useEffect, useState } from "react";
import { redirectToLogin } from "../../common/redirectToLogin";

interface IMetrics {
    panels?: number;
    panelsWithPanelists?: number;
    inPersonSlots?: number;
    onlineSlots?: number;
    respondants?: number;
    panelists?: number;
}

const MetricsView: React.FC<{}> = () => {

    const [metrics, setMetrics] = useState<IMetrics>({});

    function fetchMetrics() {
        axios.get('/api/scheduler/initial_metrics.php')
            .then(res => {
                setMetrics(res.data)
            })
            .catch(error => {
                if (error.response && error.response.status === 401) {
                    redirectToLogin();
                }
            }
        );
    }


    useEffect(() => fetchMetrics(), []);

    return (<>
        <p>The auto-scheduler is a tool that can analyze the results of the interest survey,
            and use those results to perform a first pass at populating the schedule.</p>
        <div className="row">
            <div className="col-md-6">
                <table className="table table-bordered">
                    <tbody>
                        <tr>
                            <th rowSpan={2}>Session suggestions</th>
                            <td>Total</td>
                            <td className="text-center">{metrics?.panels}</td>
                        </tr>
                        <tr>
                            <td>With Potential Panelists</td>
                            <td className="text-center">{metrics?.panelsWithPanelists}</td>
                        </tr>
                        <tr>
                            <th rowSpan={2}><a href="../TimeSlot.php">Available panel slots</a></th>
                            <td>In-Person</td>
                            <td className="text-center">{metrics?.inPersonSlots}</td>
                        </tr>
                        <tr>
                            <td>Online</td>
                            <td className="text-center">{metrics?.onlineSlots}</td>
                        </tr>
                        <tr>
                            <th colSpan={2}>Interest survey respondants</th>
                            <td className="text-center">{metrics?.respondants}</td>
                        </tr>
                        <tr>
                            <th colSpan={2}>Potential panelist respondants</th>
                            <td className="text-center">{metrics?.panelists}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </>);
}

export default MetricsView;